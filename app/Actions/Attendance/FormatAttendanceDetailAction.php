<?php

namespace App\Actions\Attendance;

use App\Models\AttendanceCorrectRequest;
use App\Models\AttendanceRecord;
use App\Models\BreakTime;
use App\Models\ProposalBreak;

class FormatAttendanceDetailAction
{
    /**
     * @return array<string, mixed>
     */
    public function __invoke(AttendanceRecord $attendanceRecord): array
    {
        $pendingRequest = $attendanceRecord->correctRequests()
            ->whereNull('approved_at')
            ->with('proposalBreaks')
            ->latest()
            ->first();

        $data = $pendingRequest
            ? $this->formatPendingRequest($pendingRequest)
            : $this->formatAttendanceRecord($attendanceRecord);

        $data['id'] = $attendanceRecord->id;
        $data['application'] = $pendingRequest;

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function formatAttendanceRecord(AttendanceRecord $attendanceRecord): array
    {
        return [
            'year' => $attendanceRecord->work_date->format('Y').'年',
            'date' => $attendanceRecord->work_date->isoFormat('M月D日'),
            'clock_in' => $attendanceRecord->clock_in_time?->format('H:i') ?? '',
            'clock_out' => $attendanceRecord->clock_out_time?->format('H:i') ?? '',
            'breaks' => $attendanceRecord->breaks->map(fn (BreakTime $break) => [
                'break_in' => $break->break_start_time?->format('H:i') ?? '',
                'break_out' => $break->break_end_time?->format('H:i') ?? '',
            ]),
            'comment' => '',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatPendingRequest(AttendanceCorrectRequest $correctRequest): array
    {
        return [
            'year' => $correctRequest->new_date->format('Y').'年',
            'date' => $correctRequest->new_date->isoFormat('M月D日'),
            'clock_in' => $correctRequest->new_clock_in,
            'clock_out' => $correctRequest->new_clock_out,
            'breaks' => $correctRequest->proposalBreaks->map(fn (ProposalBreak $break) => [
                'break_in' => $break->break_in,
                'break_out' => $break->break_out ?? '',
            ]),
            'comment' => $correctRequest->comment,
        ];
    }
}
