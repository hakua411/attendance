<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            User::factory()->create([
                'name' => 'ユーザー1',
                'email' => 'user1@example.com',
                'admin_status' => false,
            ]),

            User::factory()->create([
                'name' => 'ユーザー2',
                'email' => 'user2@example.com',
                'admin_status' => false,
            ]),

            User::factory()->create([
                'name' => 'ユーザー3',
                'email' => 'user3@example.com',
                'admin_status' => true,
            ]),
        ];

        foreach ($users as $user) {
            $date = now()->subMonth()->startOfMonth();

            while ($date->lte(now()->subDay())) {

                // 土日は勤務しない
                if ($date->isWeekday()) {

                    // 約90%の確率で出勤
                    if (fake()->boolean(90)) {

                        $attendance = Attendance::factory()->create([
                            'user_id' => $user->id,
                            'date' => $date->format('Y-m-d'),
                        ]);

                        // 休憩を1件作成
                        AttendanceBreak::factory()->create([
                            'attendance_record_id' => $attendance->id,
                            'break_in' => '12:00:00',
                            'break_out' => '13:00:00',
                        ]);
                    }
                }

                $date->addDay();
            }
        }
    }
}
