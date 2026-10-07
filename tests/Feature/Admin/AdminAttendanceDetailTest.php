<?php

namespace Tests\Feature\Admin;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_勤怠詳細画面に選択した勤怠情報が表示される()
    {
        $admin = User::factory()->create([
            'admin_status' => 1,
        ]);

        $user = User::factory()->create([
            'admin_status' => 0,
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-10-07',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => '通常勤務',
        ]);

        $response = $this->actingAs($admin)
            ->get("/admin/attendance/{$attendance->id}");

        $response->assertStatus(200);

        $response->assertViewHas('attendanceRecord', function ($attendanceRecord) use ($attendance) {
            return $attendanceRecord['id'] === $attendance->id
                && $attendanceRecord['year'] === '2026年'
                && $attendanceRecord['date'] === '10月07日'
                && $attendanceRecord['clock_in'] === '09:00'
                && $attendanceRecord['clock_out'] === '18:00'
                && $attendanceRecord['comment'] === '通常勤務';
        });

        $response->assertViewHas('user', $user);
    }

    public function test_出勤時間が退勤時間より後の場合エラーメッセージが表示される()
    {
        $admin = User::factory()->create([
            'admin_status' => 1,
        ]);

        $user = User::factory()->create([
            'admin_status' => 0,
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-10-07',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => '通常勤務',
        ]);

        $response = $this->actingAs($admin)
            ->post("/admin/attendance/{$attendance->id}", [
                'new_clock_in' => '19:00',
                'new_clock_out' => '18:00',
                'comment' => '通常勤務',
            ]);

        $response->assertSessionHasErrors([
            'new_clock_in' => '出勤時間もしくは退勤時間が不適切な値です',
        ]);
    }

    public function test_休憩開始時間が退勤時間より後の場合エラーメッセージが表示される()
    {
        $admin = User::factory()->create([
            'admin_status' => 1,
        ]);

        $user = User::factory()->create([
            'admin_status' => 0,
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-10-07',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => '通常勤務',
        ]);

        $response = $this->actingAs($admin)
            ->post("/admin/attendance/{$attendance->id}", [
                'new_clock_in' => '09:00',
                'new_clock_out' => '18:00',
                'new_break_in' => ['19:00'],
                'new_break_out' => ['19:30'],
                'comment' => '通常勤務',
            ]);

        $response->assertSessionHasErrors([
            'new_break_in.0' => '休憩時間が不適切な値です',
        ]);
    }

    public function test_休憩終了時間が退勤時間より後の場合エラーメッセージが表示される()
    {
        $admin = User::factory()->create([
            'admin_status' => 1,
        ]);

        $user = User::factory()->create([
            'admin_status' => 0,
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-10-07',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => '通常勤務',
        ]);

        $response = $this->actingAs($admin)
            ->post("/admin/attendance/{$attendance->id}", [
                'new_clock_in' => '09:00',
                'new_clock_out' => '18:00',
                'new_break_in' => ['12:00'],
                'new_break_out' => ['19:00'],
                'comment' => '通常勤務',
            ]);

        $response->assertSessionHasErrors([
            'new_break_out.0' => '休憩時間もしくは退勤時間が不適切な値です',
        ]);
    }

    public function test_備考欄が未入力の場合エラーメッセージが表示される()
    {
        $admin = User::factory()->create([
            'admin_status' => 1,
        ]);

        $user = User::factory()->create([
            'admin_status' => 0,
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-10-07',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => '通常勤務',
        ]);

        $response = $this->actingAs($admin)
            ->post("/admin/attendance/{$attendance->id}", [
                'new_clock_in' => '09:00',
                'new_clock_out' => '18:00',
                'new_break_in' => ['12:00'],
                'new_break_out' => ['13:00'],
                'comment' => '',
            ]);

        $response->assertSessionHasErrors([
            'comment' => '備考を記入してください',
        ]);
    }
}
