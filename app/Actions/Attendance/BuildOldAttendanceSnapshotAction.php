<?php

namespace App\Actions\Attendance;

use App\Models\AttendanceRecord;
use App\Models\BreakTime;

class BuildOldAttendanceSnapshotAction
{
    /**
     * @return array<string, mixed>
     */
    public function __invoke(AttendanceRecord $attendanceRecord): array
    {
        return [
            'old_date' => $attendanceRecord->date,
            'old_clock_in' => $attendanceRecord->clock_in_time,
            'old_clock_out' => $attendanceRecord->clock_out_time,
            'old_comment' => $attendanceRecord->comment,
            'old_breaks' => $attendanceRecord->breaks->map(fn (BreakTime $break): array => [
                'break_in' => $break->break_start_time?->format('H:i') ?? '',
                'break_out' => $break->break_end_time?->format('H:i') ?? '',
            ])->all(),
        ];
    }
}
