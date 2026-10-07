<?php

namespace Tests\Feature\Attendance;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClockOutTest extends TestCase
{
    use RefreshDatabase;

    public function test_退勤ボタンが正しく機能する()
    {
        // 9:00に出勤
        Carbon::setTestNow(Carbon::create(2026, 10, 5, 9, 0));

        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/attendance', [
                'action' => 'clock_in',
            ]);

        // 18:00に退勤
        Carbon::setTestNow(Carbon::create(2026, 10, 5, 18, 0));

        $this->actingAs($user)
            ->post('/attendance', [
                'action' => 'clock_out',
            ]);

        // 退勤後は退勤済
        $response = $this->actingAs($user)
            ->get('/attendance');

        $response->assertViewHas('user', function ($viewUser) {
            return $viewUser->attendance_status === '退勤済';
        });
    }

    public function test_退勤時刻が勤怠一覧画面で確認できる()
    {
        // 9:00に出勤
        Carbon::setTestNow(Carbon::create(2026, 10, 5, 9, 0));

        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/attendance', [
                'action' => 'clock_in',
            ]);

        // 18:00に退勤
        $clockOut = Carbon::create(2026, 10, 5, 18, 0);

        Carbon::setTestNow($clockOut);

        $this->actingAs($user)
            ->post('/attendance', [
                'action' => 'clock_out',
            ]);

        // 勤怠一覧を開く
        $response = $this->actingAs($user)
            ->get('/attendance/list');

        $response->assertStatus(200);

        $response->assertViewHas('formattedAttendanceRecords', function ($records) use ($clockOut) {
            $attendance = $records->first();

            return $attendance
                && $attendance['clock_out'] === $clockOut->format('H:i');
        });
    }
}
