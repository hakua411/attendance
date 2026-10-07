<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceListTest extends TestCase
{
    use RefreshDatabase;

    public function test_自分が行った勤怠情報が全て表示される()
    {
        $user = User::factory()->create();

        $otherUser = User::factory()->create();

        $attendance1 = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-10-01',
        ]);

        $attendance2 = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-10-02',
        ]);

        $otherAttendance = Attendance::factory()->create([
            'user_id' => $otherUser->id,
            'date' => '2026-10-03',
        ]);

        $response = $this->actingAs($user)
            ->get('/attendance/list');

        $response->assertStatus(200);

        $response->assertViewHas('formattedAttendanceRecords', function ($records) use (
            $attendance1,
            $attendance2,
            $otherAttendance
        ) {
            $ids = collect($records)->pluck('id')->all();

            return in_array($attendance1->id, $ids)
                && in_array($attendance2->id, $ids)
                && ! in_array($otherAttendance->id, $ids);
        });
    }

    public function test_勤怠一覧画面に遷移した際に現在の月が表示される()
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 6));

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get('/attendance/list');

        $response->assertStatus(200);

        $response->assertViewHas('date', function ($date) {
            return $date->format('Y-m') === '2026-10';
        });
    }

    public function test_前月を押下した時に表示月の前月の情報が表示される()
    {
        $user = User::factory()->create();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-09-15',
        ]);

        $response = $this->actingAs($user)
            ->get('/attendance/list?date=2026-09');

        $response->assertStatus(200);

        $response->assertViewHas('date', function ($date) {
            return $date->format('Y-m') === '2026-09';
        });

        $response->assertViewHas('formattedAttendanceRecords', function ($records) use ($attendance) {
            $ids = collect($records)->pluck('id')->all();

            return in_array($attendance->id, $ids);
        });
    }

    public function test_翌月を押下した時に表示月の翌月の情報が表示される()
    {
        $user = User::factory()->create();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-10-15',
        ]);

        $response = $this->actingAs($user)
            ->get('/attendance/list?date=2026-10');

        $response->assertStatus(200);

        $response->assertViewHas('date', function ($date) {
            return $date->format('Y-m') === '2026-10';
        });

        $response->assertViewHas('formattedAttendanceRecords', function ($records) use ($attendance) {
            $ids = collect($records)->pluck('id')->all();

            return in_array($attendance->id, $ids);
        });
    }

    public function test_詳細を押下するとその日の勤怠詳細画面に遷移する()
    {
        $user = User::factory()->create();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-10-05',
        ]);

        $response = $this->actingAs($user)
            ->get('/attendance/'.$attendance->id);

        $response->assertStatus(200);

        $response->assertViewIs('user.user-detail');

        $response->assertViewHas('data', function ($data) use ($attendance) {
            return $data['id'] === $attendance->id
                && $data['date'] === '10/05';
        });
    }
}
