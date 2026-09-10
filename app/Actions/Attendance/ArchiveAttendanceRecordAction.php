<?php

namespace App\Actions\Attendance;

use App\Models\AttendanceCorrectRequest;
use App\Models\AttendanceRecord;
use App\Models\AttendanceRecordHistory;
use App\Models\BreakTime;

class ArchiveAttendanceRecordAction
{
    public function __invoke(AttendanceRecord $attendanceRecord, AttendanceCorrectRequest $correctRequest): AttendanceRecordHistory
    {
        return AttendanceRecordHistory::create([
            'attendance_record_id' => $attendanceRecord->id,
            'attendance_correct_request_id' => $correctRequest->id,
            'date' => $attendanceRecord->date,
            'clock_in_time' => $attendanceRecord->clock_in_time,
            'clock_out_time' => $attendanceRecord->clock_out_time,
            'comment' => $attendanceRecord->comment,
            'breaks' => $attendanceRecord->breaks->map(fn (BreakTime $break): array => [
                'break_in' => $break->break_start_time?->format('H:i') ?? '',
                'break_out' => $break->break_end_time?->format('H:i') ?? '',
            ])->all(),
        ]);
    }
}
