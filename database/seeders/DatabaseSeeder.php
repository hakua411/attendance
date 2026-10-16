<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ユーザーを作成する。すでに存在する場合は再利用する。
        $user1 = User::updateOrCreate(
            ['email' => 'user1@example.com'],
            [
                'name' => 'ユーザー1',
                'password' => bcrypt('password'),
                'admin_status' => false,
                'email_verified_at' => now(),
            ]
        );

        $user2 = User::updateOrCreate(
            ['email' => 'user2@example.com'],
            [
                'name' => 'ユーザー2',
                'password' => bcrypt('password'),
                'admin_status' => false,
                'email_verified_at' => now(),
            ]
        );

        $user3 = User::updateOrCreate(
            ['email' => 'user3@example.com'],
            [
                'name' => 'ユーザー3',
                'password' => bcrypt('password'),
                'admin_status' => true,
                'email_verified_at' => now(),
            ]
        );

        /*
         * user1：集計テスト用の固定データ
         */

        // 今月を含む過去6か月の範囲
        $startDate = now()->startOfMonth()->subMonths(5);
        $endDate = now()->endOfMonth();

        // user1の対象期間内の勤怠・休憩データだけ削除する
        $attendanceIds = Attendance::where('user_id', $user1->id)
            ->whereBetween('date', [
                $startDate->toDateString(),
                $endDate->toDateString(),
            ])
            ->pluck('id');

        AttendanceBreak::whereIn('attendance_record_id', $attendanceIds)
            ->delete();

        Attendance::whereIn('id', $attendanceIds)->delete();

        // 勤怠1件と休憩1件を作成するための共通処理
        $createAttendance = function (
            Carbon $date,
            string $clockIn,
            string $clockOut
        ) use ($user1): void {
            $attendance = Attendance::create([
                'user_id' => $user1->id,
                'date' => $date->toDateString(),
                'clock_in' => $date->toDateString().' '.$clockIn,
                'clock_out' => $date->toDateString().' '.$clockOut,
            ]);

            AttendanceBreak::create([
                'attendance_record_id' => $attendance->id,
                'break_in' => '12:00:00',
                'break_out' => '13:00:00',
            ]);
        };

        // 過去5か月：各月の平日15日分を通常勤務にする
        for ($monthOffset = 5; $monthOffset >= 1; $monthOffset--) {
            $monthStart = now()
                ->startOfMonth()
                ->subMonths($monthOffset);

            $date = $monthStart->copy();
            $count = 0;

            while (
                $count < 15
                && $date->month === $monthStart->month
                && $date->year === $monthStart->year
            ) {
                if ($date->isWeekday()) {
                    $createAttendance(
                        $date->copy(),
                        '09:00:00',
                        '18:00:00'
                    );

                    $count++;
                }

                $date->addDay();
            }
        }

        // 今月：17件の指定パターンを作成する
        $currentMonth = now()->startOfMonth();

        $patterns = [
            // 通常勤務：10件
            ...array_fill(0, 10, ['09:00:00', '18:00:00']),

            // 残業：3件
            ...array_fill(0, 3, ['09:00:00', '20:00:00']),

            // 遅刻：2件
            ...array_fill(0, 2, ['09:30:00', '18:00:00']),

            // 早退：1件
            ['09:00:00', '17:00:00'],

            // 長時間勤務：1件
            ['08:00:00', '21:00:00'],
        ];

        foreach ($patterns as $index => [$clockIn, $clockOut]) {
            $date = $currentMonth->copy()->addDays($index);

            $createAttendance($date, $clockIn, $clockOut);
        }

        /*
         * user2・user3：ランダムデータ
         */

        foreach ([$user2, $user3] as $user) {
            $date = now()->subMonth()->startOfMonth();

            while ($date->lte(now()->subDay())) {
                if ($date->isWeekday()) {
                    if (fake()->boolean(90)) {
                        $attendance = Attendance::firstOrCreate(
                            [
                                'user_id' => $user->id,
                                'date' => $date->format('Y-m-d'),
                            ],
                            [
                                'clock_in' => $date->format('Y-m-d').' 09:00:00',
                                'clock_out' => $date->format('Y-m-d').' 18:00:00',
                            ]
                        );

                        // 既存の勤怠があった場合は、休憩データも重複作成しない
                        if ($attendance->wasRecentlyCreated) {
                            AttendanceBreak::create([
                                'attendance_record_id' => $attendance->id,
                                'break_in' => '12:00:00',
                                'break_out' => '13:00:00',
                            ]);
                        }

                    }
                }

                $date->addDay();
            }
        }
    }
}
