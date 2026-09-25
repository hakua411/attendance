<?php

namespace App\Http\Controllers;

use App\Http\Requests\CorrectionRequest;
use App\Models\Application;
use App\Models\ApplicationBreak;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function create()
    {
        $user = auth()->user();

        $formattedDate = Carbon::now()->isoFormat('YYYY年MM月DD日(ddd)');
        $formattedTime = Carbon::now()->format('H:i');

        return view('user.attendance-register', compact(
            'user',
            'formattedDate',
            'formattedTime'
        ));
    }

    public function clockIn()
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

    public function breakIn()
    {
        $user = auth()->user();

        $attendance = $user->attendances()
            ->whereDate('date', today())
            ->first();

        AttendanceBreak::create([
            'attendance_record_id' => $attendance->id,
            'break_in' => Carbon::now(),
        ]);

        return redirect()->route('attendance.create');
    }

    public function breakOut()
    {
        $user = auth()->user();

        $attendance = $user->attendances()
            ->whereDate('date', today())
            ->first();

        $break = $attendance->breaks()
            ->whereNull('break_out')
            ->latest()
            ->first();

        $break->update([
            'break_out' => Carbon::now(),
        ]);

        return redirect()->route('attendance.create');
    }

    public function clockOut()
    {
        $user = auth()->user();

        $attendance = $user->attendances()
            ->whereDate('date', today())
            ->first();

        if ($attendance->clock_out) {
            return redirect()->route('attendance.create');
        }

        $attendance->update([
            'clock_out' => Carbon::now(),
        ]);

        return redirect()->route('attendance.create');
    }

    public function store(Request $request)
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
    }

    public function correctionRequest(CorrectionRequest $request, $id)
    {
        $validated = $request->validated();

        $attendance = Attendance::findOrFail($id);

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
