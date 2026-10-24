<?php

namespace Tests\Feature\Api\V1;

use App\Models\Application;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AttendanceRecordApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 勤怠一覧をJSONで取得でき、ページネーション情報が返る。
     */
    public function test_index_returns_attendance_records_with_pagination(): void
    {
        $user = User::factory()->create();

        Attendance::factory()
            ->for($user)
            ->sequence(
                ['date' => '2026-10-20'],
                ['date' => '2026-10-21'],
                ['date' => '2026-10-22'],
            )
            ->count(3)
            ->create();

        $response = $this->getJson('/api/v1/attendance-records');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'date',
                        'clock_in',
                        'clock_out',
                        'user_name',
                        'breaks',
                    ],
                ],
                'links',
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ]);

        $this->assertSame(3, $response->json('meta.total'));
    }

    /**
     * 勤怠詳細にユーザー・休憩・修正申請が含まれる。
     */
    public function test_show_returns_attendance_detail_with_relations(): void
    {
        $user = User::factory()->create();

        $attendance = Attendance::factory()
            ->for($user)
            ->create();

        AttendanceBreak::factory()
            ->for($attendance, 'attendance')
            ->create();

        Application::factory()
            ->for($user)
            ->for($attendance, 'attendance')
            ->create();

        $response = $this->getJson(
            "/api/v1/attendance-records/{$attendance->id}"
        );

        $response->assertOk()
            ->assertJsonPath('data.id', $attendance->id)
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.user.name', $user->name)
            ->assertJsonStructure([
                'data' => [
                    'user',
                    'breaks',
                    'applications',
                ],
            ]);

        $this->assertCount(1, $response->json('data.breaks'));
        $this->assertCount(1, $response->json('data.applications'));
    }

    /**
     * 存在しないIDの詳細取得は404とエラーJSONを返す。
     */
    public function test_show_returns_404_for_missing_record(): void
    {
        $response = $this->getJson('/api/v1/attendance-records/99999');

        $response->assertNotFound()
            ->assertExactJson([
                'error' => '勤怠情報が見つかりませんでした。',
            ]);
    }

    /**
     * POSTで勤怠を作成できる。
     */
    public function test_store_creates_attendance_record(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $payload = [
            'date' => '2026-10-01',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => 'APIテスト',
        ];

        $response = $this->postJson(
            '/api/v1/attendance-records',
            $payload
        );

        $response->assertCreated()
            ->assertJsonPath('data.user_id', $user->id)
            ->assertJsonPath('data.date', $payload['date'])
            ->assertJsonPath('data.comment', $payload['comment']);

        $this->assertDatabaseHas('attendance_records', [
            'user_id' => $user->id,
            'date' => $payload['date'].' 00:00:00',
            'clock_in' => now()->toDateString().' 09:00:00',
            'clock_out' => now()->toDateString().' 18:00:00',
            'comment' => $payload['comment'],
        ]);
    }

    /**
     * 必須項目が欠けている場合は422と日本語エラーを返す。
     */
    public function test_store_returns_422_with_japanese_validation_errors(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson(
            '/api/v1/attendance-records',
            []
        );

        $response->assertUnprocessable()
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'date',
                    'clock_in',
                ],
            ])
            ->assertJsonPath('errors.date.0', '勤怠日は必須です。')
            ->assertJsonPath('errors.clock_in.0', '出勤時刻は必須です。');
    }

    /**
     * PUTで勤怠を更新でき、存在しないIDでは404を返す。
     */
    public function test_update_updates_record_and_returns_404_for_missing_record(): void
    {
        $user = User::factory()->create();

        $attendance = Attendance::factory()
            ->for($user)
            ->create([
                'date' => '2026-10-01',
                'clock_in' => '09:00:00',
                'clock_out' => '18:00:00',
            ]);

        Sanctum::actingAs($user);

        $response = $this->putJson(
            "/api/v1/attendance-records/{$attendance->id}",
            [
                'clock_in' => '08:30:00',
                'clock_out' => '17:30:00',
                'comment' => '更新後のコメント',
            ]
        );

        $response->assertOk()
            ->assertJsonPath('data.clock_in', '08:30:00')
            ->assertJsonPath('data.clock_out', '17:30:00')
            ->assertJsonPath('data.comment', '更新後のコメント');

        $this->assertDatabaseHas('attendance_records', [
            'id' => $attendance->id,
            'clock_in' => now()->toDateString().' 08:30:00',
            'clock_out' => now()->toDateString().' 17:30:00',
            'comment' => '更新後のコメント',
        ]);

        $this->putJson(
            '/api/v1/attendance-records/99999',
            ['comment' => '更新テスト']
        )->assertNotFound();
    }

    /**
     * DELETEで勤怠を削除でき、存在しないIDでは404を返す。
     */
    public function test_destroy_deletes_record_and_returns_404_for_missing_record(): void
    {
        $user = User::factory()->create();

        $attendance = Attendance::factory()
            ->for($user)
            ->create();

        Sanctum::actingAs($user);

        $this->deleteJson(
            "/api/v1/attendance-records/{$attendance->id}"
        )->assertNoContent();

        $this->assertDatabaseMissing('attendance_records', [
            'id' => $attendance->id,
        ]);

        $this->deleteJson(
            '/api/v1/attendance-records/99999'
        )->assertNotFound();
    }

    /**
     * 未認証ではPOST・PUT・DELETEが401を返す。
     */
    public function test_write_endpoints_return_401_when_unauthenticated(): void
    {
        $user = User::factory()->create();

        $attendance = Attendance::factory()
            ->for($user)
            ->create();

        $expected = ['message' => 'Unauthenticated.'];

        $this->postJson('/api/v1/attendance-records', [
            'date' => '2026-10-01',
            'clock_in' => '09:00:00',
        ])->assertUnauthorized()
            ->assertExactJson($expected);

        $this->putJson(
            "/api/v1/attendance-records/{$attendance->id}",
            ['comment' => '更新テスト']
        )->assertUnauthorized()
            ->assertExactJson($expected);

        $this->deleteJson(
            "/api/v1/attendance-records/{$attendance->id}"
        )->assertUnauthorized()
            ->assertExactJson($expected);
    }

    /**
     * 認証済みユーザーは自分の勤怠を更新・削除できる。
     */
    public function test_user_can_update_and_delete_own_attendance_records(): void
    {
        $user = User::factory()->create();

        $attendanceToUpdate = Attendance::factory()
            ->for($user)
            ->create([
                'date' => '2026-10-01',
                'clock_in' => '09:00:00',
                'clock_out' => '18:00:00',
            ]);

        $attendanceToDelete = Attendance::factory()
            ->for($user)
            ->create([
                'date' => '2026-10-02',
            ]);

        Sanctum::actingAs($user);

        $this->putJson(
            "/api/v1/attendance-records/{$attendanceToUpdate->id}",
            ['comment' => '本人による更新']
        )->assertOk()
            ->assertJsonPath('data.comment', '本人による更新');

        $this->assertDatabaseHas('attendance_records', [
            'id' => $attendanceToUpdate->id,
            'comment' => '本人による更新',
        ]);

        $this->deleteJson(
            "/api/v1/attendance-records/{$attendanceToDelete->id}"
        )->assertNoContent();

        $this->assertDatabaseMissing('attendance_records', [
            'id' => $attendanceToDelete->id,
        ]);
    }

    /**
     * 他ユーザーの勤怠は更新・削除できず403を返す。
     */
    public function test_user_cannot_update_or_delete_another_users_attendance_records(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $attendanceToUpdate = Attendance::factory()
            ->for($owner)
            ->create([
                'comment' => '変更前',
            ]);

        $attendanceToDelete = Attendance::factory()
            ->for($owner)
            ->create([
                'date' => '2026-10-02',
            ]);

        Sanctum::actingAs($otherUser);

        $expected = [
            'error' => 'この操作を実行する権限がありません。',
        ];

        $this->putJson(
            "/api/v1/attendance-records/{$attendanceToUpdate->id}",
            ['comment' => '不正な更新']
        )->assertForbidden()
            ->assertExactJson($expected);

        $this->assertDatabaseHas('attendance_records', [
            'id' => $attendanceToUpdate->id,
            'comment' => '変更前',
        ]);

        $this->deleteJson(
            "/api/v1/attendance-records/{$attendanceToDelete->id}"
        )->assertForbidden()
            ->assertExactJson($expected);

        $this->assertDatabaseHas('attendance_records', [
            'id' => $attendanceToDelete->id,
        ]);
    }
}
