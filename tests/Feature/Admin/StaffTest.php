<?php

namespace Tests\Feature\Admin;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffTest extends TestCase
{
    use RefreshDatabase;

    public function test_管理者が全一般ユーザーの氏名とメールアドレスを確認できる()
    {
        $admin = User::factory()->create([
            'admin_status' => 1,
        ]);

        $user1 = User::factory()->create([
            'name' => '一般ユーザー1',
            'email' => 'user1@example.com',
            'admin_status' => 0,
        ]);

        $user2 = User::factory()->create([
            'name' => '一般ユーザー2',
            'email' => 'user2@example.com',
            'admin_status' => 0,
        ]);

        $response = $this->actingAs($admin)
            ->get('/admin/staff/list');

        $response->assertStatus(200);

        $response->assertSee('一般ユーザー1');
        $response->assertSee('user1@example.com');

        $response->assertSee('一般ユーザー2');
        $response->assertSee('user2@example.com');
    }

    public function test_ユーザーの勤怠情報が正しく表示される()
    {
        $admin = User::factory()->create([
            'admin_status' => 1,
        ]);

        $user = User::factory()->create([
            'name' => '一般ユーザー1',
            'admin_status' => 0,
        ]);

        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-10-01',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->actingAs($admin)
            ->get("/admin/attendance/staff/{$user->id}");

        $response->assertStatus(200);

        $response->assertSee('10/01');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    public function test_前月を押下すると前月の勤怠情報が表示される()
    {
        $admin = User::factory()->create([
            'admin_status' => 1,
        ]);

        $user = User::factory()->create([
            'name' => '一般ユーザー1',
            'admin_status' => 0,
        ]);

        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-09-15',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->actingAs($admin)
            ->get("/admin/attendance/staff/{$user->id}?date=2026-09");

        $response->assertStatus(200);

        $response->assertSee('2026/09');
        $response->assertSee('09/15');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    public function test_翌月を押下すると翌月の勤怠情報が表示される()
    {
        $admin = User::factory()->create([
            'admin_status' => 1,
        ]);

        $user = User::factory()->create([
            'name' => '一般ユーザー1',
            'admin_status' => 0,
        ]);

        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-11-15',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->actingAs($admin)
            ->get("/admin/attendance/staff/{$user->id}?date=2026-11");

        $response->assertStatus(200);

        $response->assertSee('2026/11');
        $response->assertSee('11/15');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    public function test_詳細を押下するとその日の勤怠詳細画面に遷移する()
    {
        $admin = User::factory()->create([
            'admin_status' => 1,
        ]);

        $user = User::factory()->create([
            'name' => '一般ユーザー1',
            'admin_status' => 0,
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-10-01',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->actingAs($admin)
            ->get("/admin/attendance/staff/{$user->id}");

        $response->assertStatus(200);

        $response->assertSee("/attendance/{$attendance->id}");

        $detailResponse = $this->actingAs($admin)
            ->get("/attendance/{$attendance->id}");

        $detailResponse->assertStatus(200);
    }
}
