<?php

namespace Database\Factories;

use App\Models\AttendanceCorrectRequest;
use App\Models\AttendanceRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceCorrectRequest>
 */
class AttendanceCorrectRequestFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'attendance_record_id' => AttendanceRecord::factory(),
            'new_date' => today(),
            'new_clock_in' => '09:00',
            'new_clock_out' => '18:00',
            'comment' => $this->faker->sentence(),
            'approved_at' => null,
        ];
    }
}
