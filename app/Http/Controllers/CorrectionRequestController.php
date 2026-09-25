<?php

namespace App\Http\Controllers;

use App\Http\Requests\CorrectionRequest;
use App\Models\Application;
use App\Models\ApplicationBreak;
use Illuminate\Support\Facades\Auth;

class CorrectionRequestController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $applications = Application::where('user_id', $user->id)
            ->with('attendance')
            ->orderByDesc('application_date')
            ->get();

        $formattedApplications = $applications->map(function ($application) {
            return [
                'id' => $application->id,
                'approval_status' => $application->approval_status == 0
                    ? '承認待ち'
                    : '承認済み',
                'date' => $application->attendance->date,
                'comment' => $application->comment,
                'application_date' => $application->application_date,
            ];
        });

        return view('user.user-application-list', compact(
            'user',
            'formattedApplications'
        ));
    }

    public function show($id)
    {
        $user = auth()->user();

        $application = Application::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        return redirect('/attendance/'.$application->attendance_record_id);
    }

    public function store(CorrectionRequest $request, $id)
    {
        $user = auth()->user();

        $attendance = $user->attendances()
            ->where('id', $id)
            ->firstOrFail();

        $existingApplication = $attendance->applications()
            ->where('approval_status', 0)
            ->exists();

        if ($existingApplication) {
            return redirect('/attendance/'.$id);
        }

        $application = Application::create([
            'user_id' => $user->id,
            'attendance_record_id' => $attendance->id,
            'new_clock_in' => $request->new_clock_in,
            'new_clock_out' => $request->new_clock_out,
            'comment' => $request->comment,
            'approval_status' => 0,
            'application_date' => now()->toDateString(),
        ]);

        foreach ($request->new_break_in as $index => $breakIn) {
            $breakOut = $request->new_break_out[$index] ?? null;

            if (! $breakIn && ! $breakOut) {
                continue;
            }

            ApplicationBreak::create([
                'application_id' => $application->id,
                'break_in' => $breakIn,
                'break_out' => $breakOut,
            ]);
        }

        return redirect('/attendance/'.$id);
    }
}
