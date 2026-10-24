<?php

namespace App\Http\Controllers;

use App\Models\Application;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
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
            // 日本語のステータスを文字列として扱う
            $application->mergeCasts([
                'approval_status' => 'string',
            ]);

            $application->setRelation(
                'AttendanceRecord',
                $application->attendance
            );

            $application->approval_status =
                (int) $application->getRawOriginal('approval_status') === 0
                    ? '承認待ち'
                    : '承認済み';
        });

        return view('admin.admin-application-list', compact('applications'));
    }

    /**
     * 修正申請の詳細を表示する。
     *
     * @param  int  $id  修正申請ID
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
     */
    public function approve(int $id): RedirectResponse
    {
        // 承認待ちの申請だけを取得する
        $application = Application::with([
            'attendance',
            'applicationBreaks',
        ])
            ->where('approval_status', 0)
            ->findOrFail($id);

        DB::transaction(function () use ($application) {
            $attendance = $application->attendance;

            // 勤怠日付を取得する
            $date = $attendance->date->format('Y-m-d');

            // 出勤・退勤時刻を反映する
            $attendance->clock_in = $date.' '.Carbon::parse(
                $application->new_clock_in
            )->format('H:i:s');

            $attendance->clock_out = $date.' '.Carbon::parse(
                $application->new_clock_out
            )->format('H:i:s');

            $attendance->save();

            // 既存の休憩を削除する
            $attendance->breaks()->delete();

            // 申請された休憩を反映する
            foreach ($application->applicationBreaks as $applicationBreak) {
                $attendance->breaks()->create([
                    'break_in' => Carbon::parse(
                        $date.' '.Carbon::parse(
                            $applicationBreak->break_in
                        )->format('H:i:s')
                    ),
                    'break_out' => Carbon::parse(
                        $date.' '.Carbon::parse(
                            $applicationBreak->break_out
                        )->format('H:i:s')
                    ),
                ]);
            }

            // 申請を承認済みにする
            $application->approval_status = 1;
            $application->save();
        });

        return redirect('/stamp_correction_request/list');
    }
}
