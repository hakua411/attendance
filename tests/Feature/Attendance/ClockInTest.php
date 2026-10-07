<?php

namespace Tests\Feature\Attendance;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClockInTest extends TestCase
{
    use RefreshDatabase;

    public function test_出勤ボタンが正しく機能する()
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 5, 9, 0));

        $user = User::factory()->create();

        // 出勤前は勤務外
        $response = $this->actingAs($user)
            ->get('/attendance');

        $response->assertViewHas('user', function ($viewUser) {
            return $viewUser->attendance_status === '勤務外';
        });

        // 出勤する
        $this->actingAs($user)
            ->post('/attendance', [
                'action' => 'clock_in',
            ]);

        // 出勤後は出勤中
        $response = $this->actingAs($user)
            ->get('/attendance');

        $response->assertViewHas('user', function ($viewUser) {
            return $viewUser->attendance_status === '出勤中';
        });
    }

    public function test_出勤は一日一回のみできる()
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 5, 9, 0));

        $user = User::factory()->create();

        // 1回目の出勤
        $this->actingAs($user)
            ->post('/attendance', [
                'action' => 'clock_in',
            ]);

        // 2回目の出勤
        $this->actingAs($user)
            ->post('/attendance', [
                'action' => 'clock_in',
            ]);

        // 勤怠は1件だけ
        $this->assertDatabaseCount('attendance_records', 1);

        // 2回目の出勤後も出勤中
        $response = $this->actingAs($user)
            ->get('/attendance');

        $response->assertViewHas('user', function ($viewUser) {
            return $viewUser->attendance_status === '出勤中';
        });
    }

    public function test_出勤時刻が勤怠一覧画面で確認できる()
    {
        $clockIn = Carbon::create(2026, 10, 5, 9, 30);

        Carbon::setTestNow($clockIn);

        $user = User::factory()->create();

        // 出勤する
        $this->actingAs($user)
            ->post('/attendance', [
                'action' => 'clock_in',
            ]);

        // 勤怠一覧を開く
        $response = $this->actingAs($user)
            ->get('/attendance/list');

        $response->assertStatus(200);

        $response->assertViewHas('formattedAttendanceRecords', function ($records) use ($clockIn) {
            $attendance = $records->first();

            return $attendance
                && $attendance['clock_in'] === $clockIn->format('H:i');
        });
    }
}
