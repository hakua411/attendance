<?php

namespace Tests\Feature\Admin;

use App\Models\Application;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCorrectionRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_承認待ちの修正申請が全て表示されている()
    {
        // 管理者
        $admin = User::factory()->create([
            'admin_status' => 1,
        ]);

        // ユーザー1
        $user1 = User::factory()->create([
            'admin_status' => 0,
        ]);

        // ユーザー2
        $user2 = User::factory()->create([
            'admin_status' => 0,
        ]);

        // ユーザー1の勤怠
        $attendance1 = Attendance::factory()->create([
            'user_id' => $user1->id,
        ]);

        // ユーザー2の勤怠
        $attendance2 = Attendance::factory()->create([
            'user_id' => $user2->id,
        ]);

        // ユーザー1の承認待ち申請
        Application::factory()->create([
            'user_id' => $user1->id,
            'attendance_record_id' => $attendance1->id,
            'approval_status' => 0,
            'comment' => 'ユーザー1の修正申請',
        ]);

        // ユーザー2の承認待ち申請
        Application::factory()->create([
            'user_id' => $user2->id,
            'attendance_record_id' => $attendance2->id,
            'approval_status' => 0,
            'comment' => 'ユーザー2の修正申請',
        ]);

        // 承認済み申請
        Application::factory()->create([
            'user_id' => $user1->id,
            'attendance_record_id' => $attendance1->id,
            'approval_status' => 1,
            'comment' => '承認済みの申請',
        ]);

        $response = $this->actingAs($admin)
            ->get('/stamp_correction_request/list');

        $response->assertStatus(200);

        $response->assertSee('ユーザー1の修正申請');
        $response->assertSee('ユーザー2の修正申請');
        $response->assertSee('承認待ち');
    }

    public function test_承認済みの修正申請が全て表示されている()
    {
        // 管理者
        $admin = User::factory()->create([
            'admin_status' => 1,
        ]);

        // ユーザー1
        $user1 = User::factory()->create([
            'admin_status' => 0,
        ]);

        // ユーザー2
        $user2 = User::factory()->create([
            'admin_status' => 0,
        ]);

        // ユーザー1の勤怠
        $attendance1 = Attendance::factory()->create([
            'user_id' => $user1->id,
        ]);

        // ユーザー2の勤怠
        $attendance2 = Attendance::factory()->create([
            'user_id' => $user2->id,
        ]);

        // ユーザー1の承認済み申請
        Application::factory()->create([
            'user_id' => $user1->id,
            'attendance_record_id' => $attendance1->id,
            'approval_status' => 1,
            'comment' => 'ユーザー1の承認済み申請',
        ]);

        // ユーザー2の承認済み申請
        Application::factory()->create([
            'user_id' => $user2->id,
            'attendance_record_id' => $attendance2->id,
            'approval_status' => 1,
            'comment' => 'ユーザー2の承認済み申請',
        ]);

        $response = $this->actingAs($admin)
            ->get('/stamp_correction_request/list');

        $response->assertStatus(200);

        $response->assertSee('ユーザー1の承認済み申請');
        $response->assertSee('ユーザー2の承認済み申請');
        $response->assertSee('承認済み');
    }

    public function test_修正申請の詳細内容が正しく表示されている()
    {
        // 管理者
        $admin = User::factory()->create([
            'admin_status' => 1,
        ]);

        // 一般ユーザー
        $user = User::factory()->create([
            'admin_status' => 0,
            'name' => '山田太郎',
        ]);

        // 勤怠
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-10-07',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        // 修正申請
        $application = Application::factory()->create([
            'user_id' => $user->id,
            'attendance_record_id' => $attendance->id,
            'new_clock_in' => '09:10:00',
            'new_clock_out' => '17:56:00',
            'comment' => '遅刻したため',
            'approval_status' => 0,
        ]);

        // 修正申請の休憩
        $application->applicationBreaks()->create([
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        $response = $this->actingAs($admin)
            ->get('/stamp_correction_request/approve/'.$application->id);

        $response->assertStatus(200);

        // ユーザー名
        $response->assertSee('山田太郎');

        // 勤怠日
        $response->assertSee('2026年');
        $response->assertSee('10月7日');

        // 修正後の出勤・退勤
        $response->assertSee('09:10');
        $response->assertSee('17:56');

        // コメント
        $response->assertSee('遅刻したため');

        // 修正後の休憩
        $response->assertSee('12:00');
        $response->assertSee('13:00');

    }

    public function test_修正申請の承認処理が正しく行われる()
    {
        // 管理者
        $admin = User::factory()->create([
            'admin_status' => 1,
        ]);

        // 一般ユーザー
        $user = User::factory()->create([
            'admin_status' => 0,
        ]);

        // 修正前の勤怠
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        // 既存の休憩
        $attendance->breaks()->create([
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        // 修正申請
        $application = Application::factory()->create([
            'user_id' => $user->id,
            'attendance_record_id' => $attendance->id,
            'new_clock_in' => '09:10:00',
            'new_clock_out' => '17:56:00',
            'comment' => '遅刻したため',
            'approval_status' => 0,
        ]);

        // 新しい休憩
        $application->applicationBreaks()->create([
            'break_in' => '12:30:00',
            'break_out' => '13:30:00',
        ]);

        // 承認処理
        $response = $this->actingAs($admin)
            ->post('/stamp_correction_request/approve/'.$application->id);

        // 一覧へリダイレクト
        $response->assertRedirect('/stamp_correction_request/list');

        // 申請が承認済みになっている
        $this->assertDatabaseHas('applications', [
            'id' => $application->id,
            'approval_status' => 1,
        ]);

        // 勤怠の出勤・退勤が更新されている
        $this->assertDatabaseHas('attendance_records', [
            'id' => $attendance->id,
            'clock_in' => '2026-10-07 09:10:00',
            'clock_out' => '2026-10-07 17:56:00',
        ]);

        // 申請された休憩が勤怠に反映されている
        $this->assertDatabaseHas('breaks', [
            'attendance_record_id' => $attendance->id,
            'break_in' => '2026-10-07 12:30:00',
            'break_out' => '2026-10-07 13:30:00',
        ]);
    }
}
