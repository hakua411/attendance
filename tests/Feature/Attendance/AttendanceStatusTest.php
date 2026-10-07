<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_勤務外の場合_勤怠ステータスが正しく表示される()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get('/attendance');

        $response->assertViewHas('user', function ($viewUser) {
            return $viewUser->attendance_status === '勤務外';
        });
    }

    public function test_出勤中の場合_勤怠ステータスが正しく表示される()
    {
        $user = User::factory()->create();

        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => today(),
            'clock_in' => now(),
            'clock_out' => null,
        ]);

        $response = $this->actingAs($user)
            ->get('/attendance');

        $response->assertViewHas('user', function ($viewUser) {
            return $viewUser->attendance_status === '出勤中';
        });
    }

    public function test_休憩中の場合_勤怠ステータスが正しく表示される()
    {
        $user = User::factory()->create();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => today(),
            'clock_in' => now(),
            'clock_out' => null,
        ]);

        AttendanceBreak::create([
            'attendance_record_id' => $attendance->id,
            'break_in' => now(),
            'break_out' => null,
        ]);

        $response = $this->actingAs($user)
            ->get('/attendance');

        $response->assertViewHas('user', function ($viewUser) {
            return $viewUser->attendance_status === '休憩中';
        });
    }

    public function test_退勤済の場合_勤怠ステータスが正しく表示される()
    {
        $user = User::factory()->create();

        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => today(),
            'clock_in' => now(),
            'clock_out' => now(),
        ]);

        $response = $this->actingAs($user)
            ->get('/attendance');

        $response->assertViewHas('user', function ($viewUser) {
            return $viewUser->attendance_status === '退勤済';
        });
    }
}
