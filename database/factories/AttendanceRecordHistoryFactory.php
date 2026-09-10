<?php

namespace Database\Factories;

use App\Models\AttendanceCorrectRequest;
use App\Models\AttendanceRecord;
use App\Models\AttendanceRecordHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceRecordHistory>
 */
class AttendanceRecordHistoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'attendance_record_id' => AttendanceRecord::factory(),
            'attendance_correct_request_id' => AttendanceCorrectRequest::factory(),
            'date' => today(),
            'clock_in_time' => today()->setTime(9, 0),
            'clock_out_time' => today()->setTime(18, 0),
            'comment' => '',
            'breaks' => [],
        ];
    }
}
