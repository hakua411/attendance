<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;

class AttendanceReportController extends Controller
{
    /**
     * マイ勤怠レポートを表示する。
     */
    public function index(): View
    {
        $today = Carbon::today();

        // 当月を含む過去6か月の開始日・終了日
        $startDate = $today->copy()
            ->startOfMonth()
            ->subMonths(5);

        $endDate = $today->copy()->endOfMonth();

        // ログインユーザー本人の勤怠と休憩をまとめて取得する
        $attendances = Attendance::with('breaks')
            ->where('user_id', auth()->id())
            ->whereBetween('date', [
                $startDate->toDateString(),
                $endDate->toDateString(),
            ])
            ->get();

        // 各日の労働時間・残業時間を計算する
        $dailyRecords = $attendances->map(function (
            Attendance $attendance
        ): array {
            $workMinutes = 0;

            if ($attendance->clock_in && $attendance->clock_out) {
                $clockIn = Carbon::parse($attendance->clock_in);
                $clockOut = Carbon::parse($attendance->clock_out);

                $workMinutes = (int) $clockIn->diffInMinutes($clockOut);

                $breakMinutes = $attendance->breaks
                    ->filter(fn ($break) => $break->break_in && $break->break_out
                    )
                    ->sum(fn ($break) => (int) Carbon::parse($break->break_in)
                        ->diffInMinutes(
                            Carbon::parse($break->break_out)
                        )
                    );

                $workMinutes = max(
                    0,
                    $workMinutes - (int) $breakMinutes
                );
            }

            return [
                'attendance' => $attendance,
                'work_minutes' => $workMinutes,
                'overtime_minutes' => max(0, $workMinutes - 480),
            ];
        });

        // 退勤済みの勤務記録だけをサマリーの対象にする
        $completedRecords = $dailyRecords->filter(
            fn (array $record) => $record['attendance']->clock_in !== null
                && $record['attendance']->clock_out !== null
        );

        $totalWorkMinutes = (int) $completedRecords->sum('work_minutes');

        $totalOvertimeMinutes = (int) $completedRecords
            ->sum('overtime_minutes');

        $summary = [
            'total_work_minutes' => $totalWorkMinutes,
            'total_overtime_minutes' => $totalOvertimeMinutes,
            'avg_work_minutes' => $completedRecords->isEmpty()
                ? 0
                : (int) round(
                    $totalWorkMinutes / $completedRecords->count()
                ),
        ];

        // 過去6か月の月次推移。データのない月も0で表示する
        $monthlyTrend = collect(range(0, 5))->map(function (
            int $offset
        ) use ($startDate, $dailyRecords): array {
            $month = $startDate->copy()->addMonths($offset);
            $monthKey = $month->format('Y-m');

            $records = $dailyRecords->filter(
                fn (array $record) => $record['attendance']->date->format('Y-m') === $monthKey
            );

            $completed = $records->filter(
                fn (array $record) => $record['attendance']->clock_in !== null
                    && $record['attendance']->clock_out !== null
            );

            return [
                'month' => $month->format('Y年m月'),
                'work_minutes' => (int) $completed->sum('work_minutes'),
                'overtime_minutes' => (int) $completed->sum(
                    'overtime_minutes'
                ),
            ];
        });

        // 当月の異常検知
        $thisMonthRecords = $dailyRecords->filter(
            fn (array $record) => $record['attendance']->date->format('Y-m')
                    === $today->format('Y-m')
        );

        $anomalies = [
            'late_count' => $thisMonthRecords->filter(
                fn (array $record) => $record['attendance']->clock_in !== null
                    && Carbon::parse(
                        $record['attendance']->clock_in
                    )->format('H:i') > '09:00'
            )->count(),

            'early_leave_count' => $thisMonthRecords->filter(
                fn (array $record) => $record['attendance']->clock_out !== null
                    && Carbon::parse(
                        $record['attendance']->clock_out
                    )->format('H:i') < '18:00'
            )->count(),

            'long_work_count' => $thisMonthRecords->filter(
                fn (array $record) => $record['attendance']->clock_in !== null
                    && $record['attendance']->clock_out !== null
                    && $record['work_minutes'] > 600
            )->count(),
        ];

        return view('reports.index', compact(
            'summary',
            'monthlyTrend',
            'anomalies'
        ));
    }
}
