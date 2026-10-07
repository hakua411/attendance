<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Attendance $attendance;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'name' => '山田太郎',
        ]);

        $this->attendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => '2026-10-06',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        AttendanceBreak::factory()->create([
            'attendance_record_id' => $this->attendance->id,
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        AttendanceBreak::factory()->create([
            'attendance_record_id' => $this->attendance->id,
            'break_in' => '15:00:00',
            'break_out' => '15:15:00',
        ]);
    }

    public function test_勤怠詳細画面の名前がログインユーザーの氏名になっている()
    {
        $response = $this->actingAs($this->user)
            ->get('/attendance/'.$this->attendance->id);

        $response->assertStatus(200);
        $response->assertSee('山田太郎');
    }

    public function test_勤怠詳細画面の日付が選択した日付になっている()
    {
        $response = $this->actingAs($this->user)
            ->get('/attendance/'.$this->attendance->id);

        $response->assertStatus(200);
        $response->assertSee('10/06');
    }

    public function test_勤怠詳細画面の出勤退勤時間がログインユーザーの打刻と一致している()
    {
        $response = $this->actingAs($this->user)
            ->get('/attendance/'.$this->attendance->id);

        $response->assertStatus(200);
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    public function test_勤怠詳細画面の休憩時間がログインユーザーの打刻と一致している()
    {
        $response = $this->actingAs($this->user)
            ->get('/attendance/'.$this->attendance->id);

        $response->assertStatus(200);
        $response->assertSee('12:00');
        $response->assertSee('13:00');
        $response->assertSee('15:00');
        $response->assertSee('15:15');
    }
}
