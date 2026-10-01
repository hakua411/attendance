<?php

namespace App\Http\Controllers;

use App\Models\Application;

class AdminCorrectionRequestController extends Controller
{
    public function index()
    {
        $applications = Application::with(['user', 'attendance'])
            ->orderByDesc('application_date')
            ->get();

        $applications->each(function ($application) {
            $application->setRelation(
                'AttendanceRecord',
                $application->attendance
            );

            $application->approval_status = (int) $application->approval_status === 0
                ? '承認待ち'
                : '承認済み';
        });

        return view('admin.admin-application-list', compact('applications'));
    }

    public function show($id)
    {
        $application = Application::with([
            'user',
            'attendance',
            'applicationBreaks',
        ])->findOrFail($id);

        $application->setRelation(
            'proposalBreaks',
            $application->applicationBreaks
        );

        $application->new_date = $application->attendance->date;

        $application->approval_status = (int) $application->approval_status === 0
            ? '承認待ち'
            : '承認済み';

        $user = $application->user;

        return view('admin.admin-application-detail', compact(
            'application',
            'user'
        ));
    }

    public function approve($id)
    {
        $application = Application::with([
            'attendance',
            'applicationBreaks',
        ])->findOrFail($id);

        $attendance = $application->attendance;

        // 出勤・退勤を反映
        $attendance->clock_in = $application->new_clock_in;
        $attendance->clock_out = $application->new_clock_out;
        $attendance->save();

        // 既存の休憩を削除
        $attendance->breaks()->delete();

        // 申請された休憩を反映
        foreach ($application->applicationBreaks as $applicationBreak) {
            $attendance->breaks()->create([
                'break_in' => $applicationBreak->break_in,
                'break_out' => $applicationBreak->break_out,
            ]);
        }

        // 申請を承認済みにする
        $application->approval_status = 1;
        $application->save();

        return redirect('/stamp_correction_request/list');
    }
}
