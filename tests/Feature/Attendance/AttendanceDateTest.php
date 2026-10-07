<?php

namespace Tests\Feature\Attendance;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceDateTest extends TestCase
{
    use RefreshDatabase;

    public function test_現在の日時情報が_u_iと同じ形式で出力されている()
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 5, 9, 30));

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get('/attendance');

        $response->assertViewHas('formattedDate', '2026年10月05日(Mon)');
        $response->assertViewHas('formattedTime', '09:30');
    }
}
