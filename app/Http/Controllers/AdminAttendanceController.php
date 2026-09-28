<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdminAttendanceController extends Controller
{
    public function index(Request $request)
    {
        // 表示する日付を取得
        $date = $request->query('date')
            ? Carbon::parse($request->query('date'))
            : Carbon::today();

        // 前日・翌日
        $previousDay = $date->copy()->subDay()->format('Y-m-d');
        $nextDay = $date->copy()->addDay()->format('Y-m-d');

        // 全ユーザーを取得
        $users = User::all();

        // 指定日の勤怠を取得
        $attendanceRecords = Attendance::with('breaks')
            ->whereDate('date', $date)
            ->get();

        // 勤務時間・休憩時間を計算
        foreach ($attendanceRecords as $attendance) {
            // 休憩時間の合計
            $totalBreakSeconds = 0;

            foreach ($attendance->breaks as $break) {
                if ($break->break_in && $break->break_out) {
                    $breakIn = Carbon::parse($break->break_in);
                    $breakOut = Carbon::parse($break->break_out);

                    $totalBreakSeconds += $breakIn->diffInSeconds($breakOut);
                }
            }

            $attendance->total_break_time = $totalBreakSeconds > 0
                ? gmdate('H:i:s', $totalBreakSeconds)
                : null;

            // 勤務時間
            if ($attendance->clock_in && $attendance->clock_out) {
                $clockIn = Carbon::parse($attendance->clock_in);
                $clockOut = Carbon::parse($attendance->clock_out);

                $workSeconds = $clockIn->diffInSeconds($clockOut);
                $workSeconds -= $totalBreakSeconds;

                $attendance->total_time = gmdate('H:i:s', $workSeconds);
            } else {
                $attendance->total_time = null;
            }
        }

        return view('admin.admin-attendance-list', compact(
            'date',
            'previousDay',
            'nextDay',
            'users',
            'attendanceRecords'
        ));
    }

    public function show($id)
    {
        $attendance = Attendance::with('breaks')->findOrFail($id);

        $user = $attendance->user;

        $breaks = $attendance->breaks->map(function ($break) {
            return [
                'break_in' => $break->break_in
                    ? Carbon::parse($break->break_in)->format('H:i')
                    : '',
                'break_out' => $break->break_out
                    ? Carbon::parse($break->break_out)->format('H:i')
                    : '',
            ];
        })->toArray();

        $attendanceRecord = [
            'id' => $attendance->id,
            'year' => $attendance->date->format('Y年'),
            'date' => $attendance->date->format('m月d日'),
            'clock_in' => $attendance->clock_in
                ? $attendance->clock_in->format('H:i')
                : '',
            'clock_out' => $attendance->clock_out
                ? $attendance->clock_out->format('H:i')
                : '',
            'breaks' => $breaks,
            'comment' => $attendance->comment ?? '',
        ];

        return view('admin.admin-detail', compact(
            'attendanceRecord',
            'user'
        ));
    }

    public function update(Request $request, $id)
    {
        $attendance = Attendance::findOrFail($id);

        $attendance->update([
            'clock_in' => $request->new_clock_in,
            'clock_out' => $request->new_clock_out,
            'comment' => $request->comment,
        ]);

        // 既存の休憩を削除
        $attendance->breaks()->delete();

        // 休憩を登録
        $breakIns = $request->new_break_in ?? [];
        $breakOuts = $request->new_break_out ?? [];

        foreach ($breakIns as $index => $breakIn) {
            $breakOut = $breakOuts[$index] ?? null;

            // 両方空欄なら登録しない
            if (empty($breakIn) && empty($breakOut)) {
                continue;
            }

            $attendance->breaks()->create([
                'break_in' => $breakIn,
                'break_out' => $breakOut,
            ]);
        }

        return redirect('/admin/attendance/'.$id);
    }
}
