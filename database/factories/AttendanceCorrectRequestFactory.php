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
            'is_direct_edit' => false,
            'old_date' => null,
            'old_clock_in' => null,
            'old_clock_out' => null,
            'old_comment' => null,
            'old_breaks' => null,
            'new_date' => today(),
            'new_clock_in' => '09:00',
            'new_clock_out' => '18:00',
            'comment' => $this->faker->sentence(),
            'approved_at' => null,
            'application_date' => today(),
        ];
    }

    /**
     * 管理者による直接修正済みの状態にする。
     */
    public function directEdit(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_direct_edit' => true,
            'approved_at' => now(),
        ]);
    }
}
