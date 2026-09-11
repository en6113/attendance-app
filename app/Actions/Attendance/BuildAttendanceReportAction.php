<?php

namespace App\Actions\Attendance;

use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class BuildAttendanceReportAction
{
    private const int STANDARD_WORK_MINUTES = 8 * 60;

    private const string START_TIME = '09:00';

    private const string END_TIME = '18:00';

    private const int LONG_WORK_MINUTES = 10 * 60;

    /**
     * @return array<string, mixed>
     */
    public function __invoke(User $user, CarbonImmutable $currentMonth): array
    {
        $startMonth = $currentMonth->subMonths(5);

        $records = $user->attendanceRecords()
            ->whereBetween('date', [$startMonth->toDateString(), $currentMonth->endOfMonth()->toDateString()])
            ->with('breaks')
            ->get();

        return [
            'summary' => $this->buildSummary($records),
            'monthlyTrend' => $this->buildMonthlyTrend($records, $startMonth),
            'anomalies' => $this->buildAnomalies($records, $currentMonth),
        ];
    }

    /**
     * @param  Collection<int, AttendanceRecord>  $records
     * @return array<string, int>
     */
    private function buildSummary(Collection $records): array
    {
        $workMinutes = $records->map(fn (AttendanceRecord $record) => $record->workMinutes());
        $totalWorkMinutes = $workMinutes->sum();

        return [
            'total_work_minutes' => $totalWorkMinutes,
            'total_overtime_minutes' => $workMinutes
                ->sum(fn (int $minutes) => max(0, $minutes - self::STANDARD_WORK_MINUTES)),
            'avg_work_minutes' => $workMinutes->isNotEmpty()
                ? (int) round($totalWorkMinutes / $workMinutes->count())
                : 0,
        ];
    }

    /**
     * @param  Collection<int, AttendanceRecord>  $records
     * @return Collection<int, array<string, mixed>>
     */
    private function buildMonthlyTrend(Collection $records, CarbonImmutable $startMonth): Collection
    {
        $recordsByMonth = $records->groupBy(fn (AttendanceRecord $record) => $record->date->format('Y-m'));

        return collect(range(0, 5))->map(function (int $i) use ($recordsByMonth, $startMonth) {
            $month = $startMonth->addMonths($i);
            $monthRecords = $recordsByMonth->get($month->format('Y-m'), collect());

            return [
                'month' => $month->isoFormat('YYYY/MM'),
                'work_minutes' => $monthRecords->sum(fn (AttendanceRecord $record) => $record->workMinutes()),
                'overtime_minutes' => $monthRecords
                    ->sum(fn (AttendanceRecord $record) => max(0, $record->workMinutes() - self::STANDARD_WORK_MINUTES)),
            ];
        });
    }

    /**
     * @param  Collection<int, AttendanceRecord>  $records
     * @return array<string, int>
     */
    private function buildAnomalies(Collection $records, CarbonImmutable $currentMonth): array
    {
        $currentMonthRecords = $records->filter(
            fn (AttendanceRecord $record) => $record->date->isSameMonth($currentMonth)
        );

        return [
            'late_count' => $currentMonthRecords
                ->filter(fn (AttendanceRecord $record) => $record->clock_in_time && $record->clock_in_time->format('H:i') > self::START_TIME)
                ->count(),
            'early_leave_count' => $currentMonthRecords
                ->filter(fn (AttendanceRecord $record) => $record->clock_out_time && $record->clock_out_time->format('H:i') < self::END_TIME)
                ->count(),
            'long_work_count' => $currentMonthRecords
                ->filter(fn (AttendanceRecord $record) => $record->workMinutes() > self::LONG_WORK_MINUTES)
                ->count(),
        ];
    }
}
