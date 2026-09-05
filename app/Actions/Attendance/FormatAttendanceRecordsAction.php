<?php

namespace App\Actions\Attendance;

use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class FormatAttendanceRecordsAction
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function __invoke(User $user, CarbonImmutable $date): Collection
    {
        $recordsByDate = $user->attendanceRecords()
            ->whereBetween('work_date', [$date->toDateString(), $date->endOfMonth()->toDateString()])
            ->with('breaks')
            ->get()
            ->keyBy(fn (AttendanceRecord $record) => $record->work_date->format('Y-m-d'));

        return collect(range(1, $date->daysInMonth))
            ->map(fn (int $day) => $this->formatRow($date->day($day), $recordsByDate));
    }

    /**
     * @return array<string, mixed>
     */
    private function formatRow(CarbonImmutable $day, Collection $recordsByDate): array
    {
        $record = $recordsByDate->get($day->format('Y-m-d'));

        if (! $record) {
            return [
                'date' => $day->isoFormat('MM/DD(ddd)'),
                'clock_in' => '',
                'clock_out' => '',
                'total_break_time' => '',
                'total_time' => '',
                'id' => null,
            ];
        }

        $totalBreakSeconds = $record->breaks->sum(
            fn ($break) => $break->break_start_time && $break->break_end_time
            ? $break->break_start_time->diffInSeconds($break->break_end_time)
            : 0
        );

        $totalWorkSeconds = $record->clock_in_time && $record->clock_out_time
            ? $record->clock_in_time->diffInSeconds($record->clock_out_time) - $totalBreakSeconds
            : null;

        return [
            'date' => $day->isoFormat('MM/DD(ddd)'),
            'clock_in' => $record->clock_in_time?->format('H:i') ?? '',
            'clock_out' => $record->clock_out_time?->format('H:i') ?? '',
            'total_break_time' => $totalBreakSeconds > 0 ? gmdate('H:i:s', $totalBreakSeconds) : '',
            'total_time' => $totalWorkSeconds !== null ? gmdate('H:i:s', $totalWorkSeconds) : '',
            'id' => $record->id,
        ];
    }
}
