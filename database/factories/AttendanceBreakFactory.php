<?php

namespace Database\Factories;

use App\Models\AttendanceBreak;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceBreak>
 */
class AttendanceBreakFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'attendance_record_id' => null,
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ];
    }
}
