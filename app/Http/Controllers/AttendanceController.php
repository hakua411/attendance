<?php

namespace App\Http\Controllers;

use App\Http\Requests\CorrectionRequest;
use App\Models\Application;
use App\Models\ApplicationBreak;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    /**
     * 勤怠登録画面を表示する。
     */
    public function create(): View
    {
        $user = auth()->user();
        $now = Carbon::now();

        $formattedDate = $now->isoFormat('YYYY年MM月DD日(ddd)');
        $formattedTime = $now->format('H:i');

        return view('user.attendance-register', compact(
            'user',
            'formattedDate',
            'formattedTime'
        ));
    }

    /**
     * 出勤時刻を登録する。
     */
    public function clockIn(): RedirectResponse
    {
        $user = auth()->user();

        $attendance = $user->attendances()
            ->whereDate('date', today())
            ->first();

        if ($attendance) {
            return redirect()->route('attendance.create');
        }

        Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'clock_in' => Carbon::now(),
        ]);

        return redirect()->route('attendance.create');
    }

    /**
     * 休憩開始時刻を登録する。
     */
    public function breakIn(): RedirectResponse
    {
        $user = auth()->user();

        $attendance = $user->attendances()
            ->whereDate('date', today())
            ->first();

        if (! $attendance || $attendance->clock_out) {
            return redirect()->route('attendance.create');
        }

        // すでに休憩中なら、新しい休憩を登録しない
        $openBreak = $attendance->breaks()
            ->whereNull('break_out')
            ->exists();

        if ($openBreak) {
            return redirect()->route('attendance.create');
        }

        AttendanceBreak::create([
            'attendance_record_id' => $attendance->id,
            'break_in' => Carbon::now(),
        ]);

        return redirect()->route('attendance.create');
    }

    /**
     * 休憩終了時刻を登録する。
     */
    public function breakOut(): RedirectResponse
    {
        $user = auth()->user();

        $attendance = $user->attendances()
            ->whereDate('date', today())
            ->first();

        if (! $attendance) {
            return redirect()->route('attendance.create');
        }

        $break = $attendance->breaks()
            ->whereNull('break_out')
            ->latest()
            ->first();

        if (! $break) {
            return redirect()->route('attendance.create');
        }

        $break->update([
            'break_out' => Carbon::now(),
        ]);

        return redirect()->route('attendance.create');
    }

    /**
     * 退勤時刻を登録する。
     */
    public function clockOut(): RedirectResponse
    {
        $user = auth()->user();

        $attendance = $user->attendances()
            ->whereDate('date', today())
            ->first();

        if (! $attendance || $attendance->clock_out) {
            return redirect()->route('attendance.create');
        }

        // 休憩中の場合は、先に休憩終了を登録する
        $openBreak = $attendance->breaks()
            ->whereNull('break_out')
            ->exists();

        if ($openBreak) {
            return redirect()->route('attendance.create');
        }

        $attendance->update([
            'clock_out' => Carbon::now(),
        ]);

        return redirect()->route('attendance.create');
    }

    /**
     * 送信された操作に応じて勤怠処理を実行する。
     *
     * @param  Request  $request  フォームから送信されたリクエスト
     */
    public function store(Request $request): RedirectResponse
    {
        if ($request->action === 'clock_in') {
            return $this->clockIn();
        }

        if ($request->action === 'break_in') {
            return $this->breakIn();
        }

        if ($request->action === 'break_out') {
            return $this->breakOut();
        }

        if ($request->action === 'clock_out') {
            return $this->clockOut();
        }

        return redirect()->route('attendance.create');
    }

    /**
     * 勤怠修正申請を登録する。
     * 管理者の場合は、管理者による直接修正を実行する。
     *
     * @param  CorrectionRequest  $request  勤怠修正申請の入力とバリデーション
     * @param  int  $id  修正対象の勤怠ID
     */
    public function correctionRequest(
        CorrectionRequest $request,
        int $id
    ): RedirectResponse {
        // 管理者の場合は、管理者による直接修正
        if (auth()->user()->admin_status == 1) {
            return app(AdminAttendanceController::class)->update($request, $id);
        }

        $validated = $request->validated();

        $attendance = Attendance::where('user_id', auth()->id())
            ->findOrFail($id);

        $application = Application::create([
            'user_id' => auth()->id(),
            'attendance_record_id' => $attendance->id,
            'new_clock_in' => $validated['new_clock_in'],
            'new_clock_out' => $validated['new_clock_out'],
            'comment' => $validated['comment'],
            'approval_status' => 0,
            'application_date' => now()->toDateString(),
        ]);

        $breakIns = $validated['new_break_in'] ?? [];
        $breakOuts = $validated['new_break_out'] ?? [];

        foreach ($breakIns as $index => $breakIn) {
            $breakOut = $breakOuts[$index] ?? null;

            // 両方空欄なら登録しない
            if (empty($breakIn) && empty($breakOut)) {
                continue;
            }

            ApplicationBreak::create([
                'application_id' => $application->id,
                'break_in' => $breakIn,
                'break_out' => $breakOut,
            ]);
        }

        return redirect('/attendance/list');
    }
}
