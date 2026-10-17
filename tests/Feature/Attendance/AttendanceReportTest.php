<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceReportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ゲストはレポートページにアクセスできない。
     */
    public function test_guest_cannot_access_attendance_report(): void
    {
        $response = $this->get('/attendance/report');

        $response->assertRedirect('/login');
    }

    /**
     * 認証ユーザーの統計情報が正しく計算される。
     */
    public function test_authenticated_user_attendance_report_statistics_are_calculated_correctly(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $baseDate = now()->startOfMonth();

        // 1日目：09:15〜17:45、実働510分
        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => $baseDate->copy()->addDays(1),
            'clock_in' => '09:15:00',
            'clock_out' => '17:45:00',
        ]);

        // 2日目：09:00〜19:00、実働600分
        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => $baseDate->copy()->addDays(2),
            'clock_in' => '09:00:00',
            'clock_out' => '19:00:00',
        ]);

        // 3日目：08:45〜19:15、実働630分
        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => $baseDate->copy()->addDays(3),
            'clock_in' => '08:45:00',
            'clock_out' => '19:15:00',
        ]);

        $response = $this->actingAs($user)
            ->get('/attendance/report');

        $response->assertOk();

        $response->assertViewHas('summary');
        $response->assertViewHas('monthlyTrend');
        $response->assertViewHas('anomalies');

        // サマリーの計算結果
        $summary = $response->viewData('summary');

        // 総労働時間：510 + 600 + 630 = 1740分
        $this->assertSame(
            1740,
            $summary['total_work_minutes']
        );

        // 残業時間：30 + 120 + 150 = 300分
        $this->assertSame(
            300,
            $summary['total_overtime_minutes']
        );

        // 平均労働時間：1740 ÷ 3 = 580分
        $this->assertSame(
            580,
            $summary['avg_work_minutes']
        );

        // 月次推移：6か月分が存在する
        $monthlyTrend = $response->viewData('monthlyTrend');

        $this->assertCount(6, $monthlyTrend);

        // 当月の集計値を確認する
        $currentMonth = $monthlyTrend->last();

        $this->assertSame(
            1740,
            $currentMonth['work_minutes']
        );

        $this->assertSame(
            300,
            $currentMonth['overtime_minutes']
        );

        // 異常検知：遅刻1件、早退1件、10時間超の勤務1件
        $anomalies = $response->viewData('anomalies');

        $this->assertSame(1, $anomalies['late_count']);
        $this->assertSame(1, $anomalies['early_leave_count']);
        $this->assertSame(1, $anomalies['long_work_count']);
    }

    /**
     * 勤怠記録がないユーザーでも安全に表示できる。
     */
    public function test_user_without_attendance_records_can_view_report(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->get('/attendance/report');

        $response->assertOk();

        $response->assertViewHas('summary');
        $response->assertViewHas('monthlyTrend');
        $response->assertViewHas('anomalies');

        // サマリーはすべて0
        $summary = $response->viewData('summary');

        $this->assertSame(0, $summary['total_work_minutes']);
        $this->assertSame(0, $summary['total_overtime_minutes']);
        $this->assertSame(0, $summary['avg_work_minutes']);

        // 月次推移は6か月分あり、各月の集計値は0
        $monthlyTrend = $response->viewData('monthlyTrend');

        $this->assertCount(6, $monthlyTrend);

        foreach ($monthlyTrend as $month) {
            $this->assertSame(0, $month['work_minutes']);
            $this->assertSame(0, $month['overtime_minutes']);
        }

        // 異常検知の件数はすべて0
        $anomalies = $response->viewData('anomalies');

        $this->assertSame(0, $anomalies['late_count']);
        $this->assertSame(0, $anomalies['early_leave_count']);
        $this->assertSame(0, $anomalies['long_work_count']);
    }
}
