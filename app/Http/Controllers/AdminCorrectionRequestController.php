<?php

namespace App\Http\Controllers;

use App\Models\Application;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminCorrectionRequestController extends Controller
{
    /**
     * 修正申請一覧を表示する。
     */
    public function index(): View
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

    /**
     * 修正申請の詳細を表示する。
     *
     * @param  int  $id  修正申請ID
     * @return View
     */
    public function show(int $id): View
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

    /**
     * 修正申請を承認し、勤怠情報に反映する。
     *
     * @param  int  $id  修正申請ID
     * @return RedirectResponse
     */
    public function approve(int $id): RedirectResponse
    {
        $application = Application::with([
            'attendance',
            'applicationBreaks',
        ])->findOrFail($id);

        $attendance = $application->attendance;

        // 出勤・退勤を反映
        $date = $attendance->date->format('Y-m-d');

        $attendance->clock_in = $date.' '.Carbon::parse($application->new_clock_in)->format('H:i:s');
        $attendance->clock_out = $date.' '.Carbon::parse($application->new_clock_out)->format('H:i:s');

        $attendance->save();

        // 既存の休憩を削除
        $attendance->breaks()->delete();

        // 申請された休憩を反映
        foreach ($application->applicationBreaks as $applicationBreak) {
            $attendance->breaks()->create([
                'break_in' => Carbon::parse(
                    $date.' '.Carbon::parse($applicationBreak->break_in)->format('H:i:s')
                ),
                'break_out' => Carbon::parse(
                    $date.' '.Carbon::parse($applicationBreak->break_out)->format('H:i:s')
                ),
            ]);
        }

        // 申請を承認済みにする
        $application->approval_status = 1;
        $application->save();

        return redirect('/stamp_correction_request/list');
    }
}
