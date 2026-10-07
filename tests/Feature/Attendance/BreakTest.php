<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BreakTest extends TestCase
{
    use RefreshDatabase;

    public function test_休憩ボタンが正しく機能する()
    {
        $breakIn = Carbon::create(2026, 10, 5, 12, 0);

        Carbon::setTestNow($breakIn);

        $user = User::factory()->create();

        // 出勤する
        $this->actingAs($user)
            ->post('/attendance', [
                'action' => 'clock_in',
            ]);

        // 休憩に入る
        $this->actingAs($user)
            ->post('/attendance', [
                'action' => 'break_in',
            ]);

        // 休憩開始時刻が登録されている
        $this->assertDatabaseHas('breaks', [
            'attendance_record_id' => Attendance::where('user_id', $user->id)->first()->id,
            'break_in' => $breakIn,
            'break_out' => null,
        ]);

        // 休憩後は「休憩中」
        $response = $this->actingAs($user)
            ->get('/attendance');

        $response->assertViewHas('user', function ($viewUser) {
            return $viewUser->attendance_status === '休憩中';
        });
    }

    public function test_休憩は一日に何回でもできる()
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 5, 9, 0));

        $user = User::factory()->create();

        // 出勤する
        $this->actingAs($user)
            ->post('/attendance', [
                'action' => 'clock_in',
            ]);

        // 作成された勤怠を取得
        $attendance = Attendance::where('user_id', $user->id)->first();

        // 1回目の休憩
        Carbon::setTestNow(Carbon::create(2026, 10, 5, 12, 0));

        $this->actingAs($user)
            ->post('/attendance', [
                'action' => 'break_in',
            ]);

        // 1回目の休憩から戻る
        Carbon::setTestNow(Carbon::create(2026, 10, 5, 12, 30));

        $this->actingAs($user)
            ->post('/attendance', [
                'action' => 'break_out',
            ]);

        // 2回目の休憩
        Carbon::setTestNow(Carbon::create(2026, 10, 5, 15, 0));

        $this->actingAs($user)
            ->post('/attendance', [
                'action' => 'break_in',
            ]);

        // 休憩が2件登録されている
        $this->assertDatabaseCount('breaks', 2);

        // 休憩データを取得
        $breaks = AttendanceBreak::where(
            'attendance_record_id',
            $attendance->id
        )->orderBy('id')->get();

        // 1回目の休憩
        $this->assertEquals(
            '12:00',
            $breaks[0]->break_in->setTimezone('Asia/Tokyo')->format('H:i')
        );

        $this->assertEquals(
            '12:30',
            $breaks[0]->break_out->setTimezone('Asia/Tokyo')->format('H:i')
        );

        // 2回目の休憩
        $this->assertEquals(
            '15:00',
            $breaks[1]->break_in->setTimezone('Asia/Tokyo')->format('H:i')
        );

        $this->assertNull($breaks[1]->break_out);
    }

    public function test_休憩戻ボタンが正しく機能する()
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 5, 9, 0));

        $user = User::factory()->create();

        // 出勤する
        $this->actingAs($user)
            ->post('/attendance', [
                'action' => 'clock_in',
            ]);

        // 休憩に入る
        Carbon::setTestNow(Carbon::create(2026, 10, 5, 12, 0));

        $this->actingAs($user)
            ->post('/attendance', [
                'action' => 'break_in',
            ]);

        // 休憩から戻る
        $breakOut = Carbon::create(2026, 10, 5, 12, 30);

        Carbon::setTestNow($breakOut);

        $this->actingAs($user)
            ->post('/attendance', [
                'action' => 'break_out',
            ]);

        // 休憩戻の時刻が登録されている
        $attendance = Attendance::where('user_id', $user->id)->first();

        $break = AttendanceBreak::where(
            'attendance_record_id',
            $attendance->id
        )->first();

        $this->assertEquals(
            '12:30',
            $break->break_out->setTimezone('Asia/Tokyo')->format('H:i')
        );

        // 休憩戻後は「出勤中」
        $response = $this->actingAs($user)
            ->get('/attendance');

        $response->assertViewHas('user', function ($viewUser) {
            return $viewUser->attendance_status === '出勤中';
        });
    }

    public function test_休憩戻は一日に何回でもできる()
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 5, 9, 0));

        $user = User::factory()->create();

        // 出勤する
        $this->actingAs($user)
            ->post('/attendance', [
                'action' => 'clock_in',
            ]);

        // 1回目の休憩
        Carbon::setTestNow(Carbon::create(2026, 10, 5, 12, 0));

        $this->actingAs($user)
            ->post('/attendance', [
                'action' => 'break_in',
            ]);

        // 1回目の休憩戻
        Carbon::setTestNow(Carbon::create(2026, 10, 5, 12, 30));

        $this->actingAs($user)
            ->post('/attendance', [
                'action' => 'break_out',
            ]);

        // 2回目の休憩
        Carbon::setTestNow(Carbon::create(2026, 10, 5, 15, 0));

        $this->actingAs($user)
            ->post('/attendance', [
                'action' => 'break_in',
            ]);

        // 2回目の休憩戻
        Carbon::setTestNow(Carbon::create(2026, 10, 5, 15, 30));

        $this->actingAs($user)
            ->post('/attendance', [
                'action' => 'break_out',
            ]);

        // 勤怠を取得
        $attendance = Attendance::where('user_id', $user->id)->first();

        // 休憩を取得
        $breaks = AttendanceBreak::where(
            'attendance_record_id',
            $attendance->id
        )->orderBy('id')->get();

        // 休憩が2件登録されている
        $this->assertCount(2, $breaks);

        // 1回目の休憩戻
        $this->assertEquals(
            '12:30',
            $breaks[0]->break_out->setTimezone('Asia/Tokyo')->format('H:i')
        );

        // 2回目の休憩戻
        $this->assertEquals(
            '15:30',
            $breaks[1]->break_out->setTimezone('Asia/Tokyo')->format('H:i')
        );

        // 最後は休憩中ではなく出勤中
        $response = $this->actingAs($user)
            ->get('/attendance');

        $response->assertViewHas('user', function ($viewUser) {
            return $viewUser->attendance_status === '出勤中';
        });
    }

    public function test_休憩時刻が勤怠一覧画面で確認できる()
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 5, 9, 0));

        $user = User::factory()->create();

        // 出勤する
        $this->actingAs($user)
            ->post('/attendance', [
                'action' => 'clock_in',
            ]);

        // 休憩に入る
        Carbon::setTestNow(Carbon::create(2026, 10, 5, 12, 0));

        $this->actingAs($user)
            ->post('/attendance', [
                'action' => 'break_in',
            ]);

        // 休憩から戻る
        Carbon::setTestNow(Carbon::create(2026, 10, 5, 12, 30));

        $this->actingAs($user)
            ->post('/attendance', [
                'action' => 'break_out',
            ]);

        // 勤怠一覧を開く
        $response = $this->actingAs($user)
            ->get('/attendance/list');

        $response->assertStatus(200);

        // 休憩時間が正しく表示されている
        $response->assertViewHas('formattedAttendanceRecords', function ($records) {
            $attendance = $records->first();

            return $attendance
                && $attendance['total_break_time'] === '00:30:00';
        });
    }
}
