<?php

namespace App\Http\Controllers;

use App\Http\Requests\CorrectionRequest;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminAttendanceController extends Controller
{
    /**
     * 指定日の全ユーザーの勤怠一覧を表示する。
     *
     * @param  Request  $request  HTTPリクエスト
     */
    public function index(Request $request): View
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

        // 指定日の勤怠を取得（休憩も先読み）
        $attendanceRecords = Attendance::with('breaks')
            ->whereDate('date', $date)
            ->get();

        // 勤務時間・休憩時間を計算
        $attendanceRecords->each(function (Attendance $attendance): void {
            // 休憩時間の合計
            $totalBreakSeconds = $attendance->breaks->sum(
                function ($break): int {
                    if (! $break->break_in || ! $break->break_out) {
                        return 0;
                    }

                    return Carbon::parse($break->break_in)
                        ->diffInSeconds(Carbon::parse($break->break_out));
                }
            );

            $attendance->total_break_time = $totalBreakSeconds > 0
                ? gmdate('H:i:s', $totalBreakSeconds)
                : null;

            // 勤務時間
            if ($attendance->clock_in && $attendance->clock_out) {
                $workSeconds = Carbon::parse($attendance->clock_in)
                    ->diffInSeconds(Carbon::parse($attendance->clock_out))
                    - $totalBreakSeconds;

                $attendance->total_time = gmdate('H:i:s', $workSeconds);
            } else {
                $attendance->total_time = null;
            }
        });

        return view('admin.admin-attendance-list', compact(
            'date',
            'previousDay',
            'nextDay',
            'users',
            'attendanceRecords'
        ));
    }

    /**
     * 指定された勤怠の詳細を表示する。
     *
     * @param  int  $id  勤怠ID
     */
    public function show(int $id): View
    {
        // 勤怠・休憩・ユーザーをまとめて先読み
        $attendance = Attendance::with(['breaks', 'user'])
            ->findOrFail($id);

        $user = $attendance->user;

        $breaks = $attendance->breaks->map(function ($break): array {
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

    /**
     * 管理者が勤怠情報を修正する。
     *
     * @param  CorrectionRequest  $request  勤怠修正フォームのリクエスト
     * @param  int  $id  修正対象の勤怠ID
     */
    public function update(
        CorrectionRequest $request,
        int $id
    ): RedirectResponse {
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
