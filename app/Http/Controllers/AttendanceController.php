<?php

namespace App\Http\Controllers;

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
}
