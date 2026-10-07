<?php

namespace Tests\Feature\Admin;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAttendanceListTest extends TestCase
{
    use RefreshDatabase;

    public function test_その日の全ユーザーの勤怠情報が正確に確認できる()
    {
        $date = '2026-10-07';

        $admin = User::factory()->create([
            'admin_status' => 1,
        ]);

        $user1 = User::factory()->create([
            'admin_status' => 0,
        ]);

        $user2 = User::factory()->create([
            'admin_status' => 0,
        ]);

        Attendance::create([
            'user_id' => $user1->id,
            'date' => $date,
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => 'ユーザー1の備考',
        ]);

        Attendance::create([
            'user_id' => $user2->id,
            'date' => $date,
            'clock_in' => '10:00:00',
            'clock_out' => '19:00:00',
            'comment' => 'ユーザー2の備考',
        ]);

        $response = $this->actingAs($admin)
            ->get('/admin/attendance/list?date='.$date);

        $response->assertStatus(200);

        $response->assertViewHas('attendanceRecords', function ($attendanceRecords) use ($user1, $user2, $date) {
            return $attendanceRecords->count() === 2
                && $attendanceRecords->contains(function ($attendance) use ($user1, $date) {
                    return $attendance->user_id === $user1->id
                        && $attendance->date->format('Y-m-d') === $date
                        && $attendance->clock_in->format('H:i:s') === '09:00:00'
                        && $attendance->clock_out->format('H:i:s') === '18:00:00'
                        && $attendance->comment === 'ユーザー1の備考';
                })
                && $attendanceRecords->contains(function ($attendance) use ($user2, $date) {
                    return $attendance->user_id === $user2->id
                        && $attendance->date->format('Y-m-d') === $date
                        && $attendance->clock_in->format('H:i:s') === '10:00:00'
                        && $attendance->clock_out->format('H:i:s') === '19:00:00'
                        && $attendance->comment === 'ユーザー2の備考';
                });
        });
    }

    public function test_遷移した際に現在の日付が表示される()
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 7));

        $admin = User::factory()->create([
            'admin_status' => 1,
        ]);

        $response = $this->actingAs($admin)
            ->get('/admin/attendance/list');

        $response->assertStatus(200);

        $response->assertViewHas('date', function ($date) {
            return $date->format('Y-m-d') === '2026-10-07';
        });
    }

    public function test_前日を押下した時に前の日の勤怠情報が表示される()
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 7));

        $admin = User::factory()->create([
            'admin_status' => 1,
        ]);

        $user = User::factory()->create([
            'admin_status' => 0,
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-10-06',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => '前日の勤怠',
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-10-07',
            'clock_in' => '10:00:00',
            'clock_out' => '19:00:00',
            'comment' => '当日の勤怠',
        ]);

        $response = $this->actingAs($admin)
            ->get('/admin/attendance/list?date=2026-10-06');

        $response->assertStatus(200);

        $response->assertViewHas('date', function ($date) {
            return $date->format('Y-m-d') === '2026-10-06';
        });

        $response->assertViewHas('attendanceRecords', function ($attendanceRecords) {
            return $attendanceRecords->count() === 1
                && $attendanceRecords->first()->date->format('Y-m-d') === '2026-10-06'
                && $attendanceRecords->first()->clock_in->format('H:i:s') === '09:00:00'
                && $attendanceRecords->first()->clock_out->format('H:i:s') === '18:00:00'
                && $attendanceRecords->first()->comment === '前日の勤怠';
        });
    }

    public function test_翌日を押下した時に次の日の勤怠情報が表示される()
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 7));

        $admin = User::factory()->create([
            'admin_status' => 1,
        ]);

        $user = User::factory()->create([
            'admin_status' => 0,
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-10-07',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => '当日の勤怠',
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-10-08',
            'clock_in' => '10:00:00',
            'clock_out' => '19:00:00',
            'comment' => '翌日の勤怠',
        ]);

        $response = $this->actingAs($admin)
            ->get('/admin/attendance/list?date=2026-10-08');

        $response->assertStatus(200);

        $response->assertViewHas('date', function ($date) {
            return $date->format('Y-m-d') === '2026-10-08';
        });

        $response->assertViewHas('attendanceRecords', function ($attendanceRecords) {
            return $attendanceRecords->count() === 1
                && $attendanceRecords->first()->date->format('Y-m-d') === '2026-10-08'
                && $attendanceRecords->first()->clock_in->format('H:i:s') === '10:00:00'
                && $attendanceRecords->first()->clock_out->format('H:i:s') === '19:00:00'
                && $attendanceRecords->first()->comment === '翌日の勤怠';
        });
    }
}
