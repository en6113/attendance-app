<?php

namespace Database\Factories;

use App\Models\AttendanceCorrectRequest;
use App\Models\ProposalBreak;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProposalBreak>
 */
class ProposalBreakFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'attendance_correct_request_id' => AttendanceCorrectRequest::factory(),
            'break_in' => '12:00',
            'break_out' => '13:00',
        ];
    }
}
