<?php

namespace Tests\Feature\Attendance;

use App\Models\Application;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CorrectionRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_出勤時間が退勤時間より後になっている場合エラーメッセージが表示される()
    {
        $user = User::factory()->create();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-10-05',
            'clock_in' => '09:00',
            'clock_out' => '18:00',
        ]);

        $response = $this->actingAs($user)
            ->post('/attendance/'.$attendance->id, [
                'new_clock_in' => '19:00',
                'new_clock_out' => '18:00',
                'new_break_in' => [],
                'new_break_out' => [],
                'comment' => '時間を修正するため',
            ]);

        $response->assertSessionHasErrors([
            'new_clock_in' => '出勤時間もしくは退勤時間が不適切な値です',
        ]);
    }

    public function test_休憩開始時間が退勤時間より後になっている場合エラーメッセージが表示される()
    {
        $user = User::factory()->create();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-10-05',
            'clock_in' => '09:00',
            'clock_out' => '18:00',
        ]);

        $response = $this->actingAs($user)
            ->post('/attendance/'.$attendance->id, [
                'new_clock_in' => '09:00',
                'new_clock_out' => '18:00',
                'new_break_in' => ['19:00'],
                'new_break_out' => ['19:30'],
                'comment' => '休憩時間を修正するため',
            ]);

        $response->assertSessionHasErrors([
            'new_break_in.0' => '休憩時間が不適切な値です',
        ]);
    }

    public function test_休憩終了時間が退勤時間より後になっている場合エラーメッセージが表示される()
    {
        $user = User::factory()->create();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-10-05',
            'clock_in' => '09:00',
            'clock_out' => '18:00',
        ]);

        $response = $this->actingAs($user)
            ->post('/attendance/'.$attendance->id, [
                'new_clock_in' => '09:00',
                'new_clock_out' => '18:00',
                'new_break_in' => ['17:00'],
                'new_break_out' => ['19:00'],
                'comment' => '休憩時間を修正するため',
            ]);

        $response->assertSessionHasErrors([
            'new_break_out.0' => '休憩時間もしくは退勤時間が不適切な値です',
        ]);
    }

    public function test_備考欄が未入力の場合エラーメッセージが表示される()
    {
        $user = User::factory()->create();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-10-05',
            'clock_in' => '09:00',
            'clock_out' => '18:00',
        ]);

        $response = $this->actingAs($user)
            ->post('/attendance/'.$attendance->id, [
                'new_clock_in' => '09:00',
                'new_clock_out' => '18:00',
                'new_break_in' => [],
                'new_break_out' => [],
                'comment' => '',
            ]);

        $response->assertSessionHasErrors([
            'comment' => '備考を記入してください',
        ]);
    }

    public function test_修正申請処理が実行される()
    {
        $user = User::factory()->create();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-10-05',
            'clock_in' => '09:00',
            'clock_out' => '18:00',
        ]);

        // 修正申請を送信
        $this->actingAs($user)
            ->post('/attendance/'.$attendance->id, [
                'new_clock_in' => '09:10',
                'new_clock_out' => '17:56',
                'new_break_in' => ['12:00'],
                'new_break_out' => ['13:00'],
                'comment' => '遅刻したため',
            ]);

        // applications に登録されていることを確認
        $this->assertDatabaseHas('applications', [
            'user_id' => $user->id,
            'attendance_record_id' => $attendance->id,
            'new_clock_in' => '09:10',
            'new_clock_out' => '17:56',
            'comment' => '遅刻したため',
            'approval_status' => 0,
        ]);

        // 申請一覧を開く
        $response = $this->actingAs($user)
            ->get('/stamp_correction_request/list');

        $response->assertStatus(200);

        // 申請一覧に表示されることを確認
        $response->assertViewHas('formattedApplications', function ($applications) {
            $application = $applications->first();

            return $application
                && $application['approval_status'] === '承認待ち'
                && $application['comment'] === '遅刻したため';
        });
    }

    public function test_承認待ちにログインユーザーが行った申請がすべて表示される()
    {
        $user = User::factory()->create();

        $attendance1 = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-10-05',
            'clock_in' => '09:00',
            'clock_out' => '18:00',
        ]);

        $attendance2 = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-10-06',
            'clock_in' => '09:00',
            'clock_out' => '18:00',
        ]);

        // 1件目の修正申請
        $this->actingAs($user)
            ->post('/attendance/'.$attendance1->id, [
                'new_clock_in' => '09:10',
                'new_clock_out' => '17:50',
                'new_break_in' => ['12:00'],
                'new_break_out' => ['13:00'],
                'comment' => '1件目の修正',
            ]);

        // 2件目の修正申請
        $this->actingAs($user)
            ->post('/attendance/'.$attendance2->id, [
                'new_clock_in' => '09:20',
                'new_clock_out' => '17:40',
                'new_break_in' => ['12:00'],
                'new_break_out' => ['13:00'],
                'comment' => '2件目の修正',
            ]);

        // 申請一覧を開く
        $response = $this->actingAs($user)
            ->get('/stamp_correction_request/list');

        $response->assertStatus(200);

        $response->assertViewHas('formattedApplications', function ($applications) {
            return $applications->count() === 2
                && $applications->every(function ($application) {
                    return $application['approval_status'] === '承認待ち';
                })
                && $applications->pluck('comment')->contains('1件目の修正')
                && $applications->pluck('comment')->contains('2件目の修正');
        });
    }

    public function test_承認済みの申請が表示される()
    {
        $user = User::factory()->create();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-10-05',
            'clock_in' => '09:00',
            'clock_out' => '18:00',
        ]);

        // 修正申請
        $this->actingAs($user)
            ->post('/attendance/'.$attendance->id, [
                'new_clock_in' => '09:10',
                'new_clock_out' => '17:56',
                'new_break_in' => ['12:00'],
                'new_break_out' => ['13:00'],
                'comment' => '遅刻したため',
            ]);

        // 管理者によって承認された状態にする
        Application::where('user_id', $user->id)
            ->update([
                'approval_status' => 1,
            ]);

        // 申請一覧を開く
        $response = $this->actingAs($user)
            ->get('/stamp_correction_request/list');

        $response->assertStatus(200);

        $response->assertViewHas('formattedApplications', function ($applications) {
            $application = $applications->first();

            return $application
                && $application['approval_status'] === '承認済み'
                && $application['comment'] === '遅刻したため';
        });
    }

    public function test_申請の詳細を押すと勤怠詳細画面へ遷移する()
    {
        $user = User::factory()->create();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-10-05',
            'clock_in' => '09:00',
            'clock_out' => '18:00',
        ]);

        // 修正申請を登録
        $this->actingAs($user)
            ->post('/attendance/'.$attendance->id, [
                'new_clock_in' => '09:10',
                'new_clock_out' => '17:56',
                'new_break_in' => ['12:00'],
                'new_break_out' => ['13:00'],
                'comment' => '遅刻したため',
            ]);

        // 登録された申請を取得
        $application = Application::first();

        // 詳細を開く
        $response = $this->actingAs($user)
            ->get('/application/'.$application->id);

        // 勤怠詳細画面へリダイレクトされる
        $response->assertRedirect('/attendance/'.$attendance->id);
    }
}
