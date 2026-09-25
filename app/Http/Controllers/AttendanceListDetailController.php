<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceListDetailController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        $date = Carbon::createFromFormat(
            'Y-m',
            $request->input('date', now()->format('Y-m'))
        );

        $previousMonth = $date->copy()->subMonth()->format('Y-m');
        $nextMonth = $date->copy()->addMonth()->format('Y-m');

        $attendances = Attendance::with('breaks')
            ->where('user_id', $user->id)
            ->whereYear('date', $date->year)
            ->whereMonth('date', $date->month)
            ->orderBy('date', 'asc')
            ->get();

        $formattedAttendanceRecords = $attendances->map(function ($attendance) {

            // 休憩時間の合計（分）
            $totalBreakMinutes = $attendance->breaks->sum(function ($break) {
                if (! $break->break_in || ! $break->break_out) {
                    return 0;
                }

                $breakIn = Carbon::parse($break->break_in);
                $breakOut = Carbon::parse($break->break_out);

                return $breakIn->diffInMinutes($breakOut);
            });

            // 実働時間（分）
            $totalWorkMinutes = null;

            if ($attendance->clock_in && $attendance->clock_out) {
                $clockIn = Carbon::parse($attendance->clock_in);
                $clockOut = Carbon::parse($attendance->clock_out);

                $totalWorkMinutes = $clockIn->diffInMinutes($clockOut)
                    - $totalBreakMinutes;
            }

            // 休憩時間を「HH:MM:SS」に変換
            $totalBreakTime = sprintf(
                '%02d:%02d:00',
                intdiv($totalBreakMinutes, 60),
                $totalBreakMinutes % 60
            );

            // 実働時間を「HH:MM:SS」に変換
            $totalTime = null;

            if ($totalWorkMinutes !== null) {
                $totalTime = sprintf(
                    '%02d:%02d:00',
                    intdiv($totalWorkMinutes, 60),
                    $totalWorkMinutes % 60
                );
            }

            return [
                'id' => $attendance->id,
                'date' => $attendance->date->format('m/d'),

                'clock_in' => $attendance->clock_in
                    ? Carbon::parse($attendance->clock_in)->format('H:i')
                    : '',

                'clock_out' => $attendance->clock_out
                    ? Carbon::parse($attendance->clock_out)->format('H:i')
                    : '',

                'total_break_time' => $totalBreakTime,
                'total_time' => $totalTime,
            ];
        });

        return view('user.user-attendance-list', compact(
            'formattedAttendanceRecords',
            'date',
            'previousMonth',
            'nextMonth'
        ));
    }

    public function show($id)
    {
        $user = auth()->user();

        $attendance = Attendance::with([
            'breaks',
            'applications',
        ])
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $application = $attendance->applications()
            ->where('approval_status', '0')
            ->latest()
            ->first();

        $data = [
            'id' => $attendance->id,
            'year' => $attendance->date->format('Y'),
            'date' => $attendance->date->format('m/d'),

            'clock_in' => $attendance->clock_in
                ? $attendance->clock_in->format('H:i')
                : '',

            'clock_out' => $attendance->clock_out
                ? $attendance->clock_out->format('H:i')
                : '',

            'breaks' => $attendance->breaks->map(function ($break) {
                return [
                    'break_in' => $break->break_in
                        ? $break->break_in->format('H:i')
                        : '',

                    'break_out' => $break->break_out
                        ? $break->break_out->format('H:i')
                        : '',
                ];
            })->values()->toArray(),

            'comment' => $attendance->comment ?? '',

            'application' => $application,
        ];

        return view('user.user-detail', compact(
            'user',
            'data'
        ));
    }
}
