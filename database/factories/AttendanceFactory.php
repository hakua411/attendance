<?php

namespace Database\Factories;

use App\Models\Attendance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attendance>
 */
class AttendanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $clockIn = fake()->dateTimeBetween('08:30', '09:30');
        $clockOut = fake()->dateTimeBetween('17:30', '19:00');

        return [
            'user_id' => null,
            'date' => now()->subDay()->format('Y-m-d'),
            'clock_in' => $clockIn->format('H:i:s'),
            'clock_out' => $clockOut->format('H:i:s'),
            'comment' => null,
        ];
    }
}
