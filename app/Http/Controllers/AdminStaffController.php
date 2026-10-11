<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

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
}
