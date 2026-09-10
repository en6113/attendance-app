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
            ->whereBetween('date', [$date->toDateString(), $date->endOfMonth()->toDateString()])
            ->with('breaks')
            ->get()
            ->keyBy(fn (AttendanceRecord $record) => $record->date->format('Y-m-d'));

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

        return [
            'date' => $day->isoFormat('MM/DD(ddd)'),
            'clock_in' => $record->clock_in,
            'clock_out' => $record->clock_out,
            'total_break_time' => $record->total_break_time,
            'total_time' => $record->total_time,
            'id' => $record->id,
        ];
    }
}
