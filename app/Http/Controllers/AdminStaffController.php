<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminStaffController extends Controller
{
    /**
     * 一般ユーザーの一覧を表示する。
     */
    public function index(): View
    {
        $users = User::where('admin_status', 0)->get();

        return view('admin.staff-list', compact('users'));
    }

    /**
     * 指定されたユーザーの月別勤怠一覧を表示する。
     *
     * @param  Request  $request  HTTPリクエスト
     * @param  int  $id  ユーザーID
     */
    public function show(Request $request, int $id): View
    {
        $user = User::findOrFail($id);

        $date = $request->query('date')
            ? Carbon::createFromFormat('!Y-m', $request->query('date'))
            : Carbon::now();

        $previousMonth = $date->copy()->subMonth()->format('Y-m');
        $nextMonth = $date->copy()->addMonth()->format('Y-m');

        $attendanceRecords = Attendance::where('user_id', $user->id)
            ->whereYear('date', $date->year)
            ->whereMonth('date', $date->month)
            ->with('breaks')
            ->orderBy('date')
            ->get();

        $formattedAttendanceRecords = $attendanceRecords->map(
            function (Attendance $attendance): array {
                $totalBreakSeconds = $attendance->breaks->sum(
                    function ($break): int {
                        if (! $break->break_in || ! $break->break_out) {
                            return 0;
                        }

                        return Carbon::parse($break->break_in)
                            ->diffInSeconds(Carbon::parse($break->break_out));
                    }
                );

                $totalTimeSeconds = 0;

                if ($attendance->clock_in && $attendance->clock_out) {
                    $totalTimeSeconds = Carbon::parse($attendance->clock_in)
                        ->diffInSeconds(Carbon::parse($attendance->clock_out))
                        - $totalBreakSeconds;
                }

                return [
                    'id' => $attendance->id,
                    'date' => $attendance->date->format('m/d'),
                    'clock_in' => $attendance->clock_in
                        ? $attendance->clock_in->format('H:i')
                        : '',
                    'clock_out' => $attendance->clock_out
                        ? $attendance->clock_out->format('H:i')
                        : '',
                    'total_break_time' => gmdate('H:i:s', $totalBreakSeconds),
                    'total_time' => gmdate('H:i:s', $totalTimeSeconds),
                ];
            }
        );

        return view('admin.staff-attendance-list', compact(
            'user',
            'date',
            'previousMonth',
            'nextMonth',
            'formattedAttendanceRecords'
        ));
    }

    /**
     * 指定されたスタッフの月別勤怠一覧をCSVで出力する。
     *
     * @param  Request  $request  HTTPリクエスト
     * @return StreamedResponse CSVダウンロードレスポンス
     */
    public function export(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'year_month' => ['required', 'date_format:Y-m'],
        ]);

        $user = User::where('admin_status', 0)
            ->findOrFail($validated['user_id']);

        $date = Carbon::createFromFormat(
            '!Y-m',
            $validated['year_month']
        );

        $attendanceRecords = Attendance::where('user_id', $user->id)
            ->whereYear('date', $date->year)
            ->whereMonth('date', $date->month)
            ->with('breaks')
            ->orderBy('date')
            ->get();

        $fileName = 'attendance_'.$user->id.'_'
            .$date->format('Y-m').'.csv';

        return response()->streamDownload(
            function () use ($attendanceRecords): void {
                $handle = fopen('php://output', 'w');

                // Excelでの日本語の文字化け対策
                fwrite($handle, "\xEF\xBB\xBF");

                fputcsv($handle, [
                    '日付',
                    '出勤時間',
                    '退勤時間',
                    '休憩時間',
                    '勤務時間',
                ]);

                foreach ($attendanceRecords as $attendance) {
                    $totalBreakSeconds = $attendance->breaks->sum(
                        function ($break): int {
                            if (! $break->break_in || ! $break->break_out) {
                                return 0;
                            }

                            return Carbon::parse($break->break_in)
                                ->diffInSeconds(
                                    Carbon::parse($break->break_out)
                                );
                        }
                    );

                    $totalWorkSeconds = null;

                    if ($attendance->clock_in && $attendance->clock_out) {
                        $totalWorkSeconds = Carbon::parse(
                            $attendance->clock_in
                        )->diffInSeconds(
                            Carbon::parse($attendance->clock_out)
                        ) - $totalBreakSeconds;
                    }

                    fputcsv($handle, [
                        $attendance->date->format('Y-m-d'),
                        $attendance->clock_in
                            ? $attendance->clock_in->format('H:i')
                            : '',
                        $attendance->clock_out
                            ? $attendance->clock_out->format('H:i')
                            : '',
                        gmdate('H:i:s', $totalBreakSeconds),
                        $totalWorkSeconds !== null
                            ? gmdate('H:i:s', max(0, $totalWorkSeconds))
                            : '',
                    ]);
                }

                fclose($handle);
            },
            $fileName,
            ['Content-Type' => 'text/csv; charset=UTF-8']
        );
    }
}
