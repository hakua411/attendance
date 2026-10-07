<?php

namespace Database\Factories;

use App\Models\Application;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Application>
 */
class ApplicationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'attendance_record_id' => Attendance::factory(),
            'new_clock_in' => '09:00:00',
            'new_clock_out' => '18:00:00',
            'comment' => '修正申請です',
            'approval_status' => 0,
            'application_date' => now()->toDateString(),
        ];
    }
}
