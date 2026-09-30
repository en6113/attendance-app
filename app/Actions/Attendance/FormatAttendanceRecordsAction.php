<?php

namespace App\Actions\Attendance;

use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * ユーザーの指定月における勤怠情報を、画面表示用に日ごとの配列へ整形するAction。
 * 未打刻でレコードが存在しない日付分もあらかじめ補完する(未来日を除く)。
 */
class FormatAttendanceRecordsAction
{
    /**
     * 指定ユーザーの指定月の勤怠情報を日ごとに整形して返す。
     * 未打刻でレコードが存在しない日付分のレコードを先に補完する（未来日を除く）。
     *
     * @param  User  $user  勤怠情報を取得する対象ユーザー
     * @param  CarbonImmutable  $date  表示対象月の1日
     * @return Collection<int, array<string, mixed>>
     */
    public function __invoke(User $user, CarbonImmutable $date): Collection
    {
        if ($date->lessThanOrEqualTo(today()->startOfMonth())) {
            $this->ensureRecordsExist($user, $date);
        }

        $recordsByDate = $user->attendanceRecords()
            ->whereBetween('date', [$date->toDateString(), $date->endOfMonth()->endOfDay()->toDateTimeString()])
            ->with('breaks')
            ->get()
            ->keyBy(fn (AttendanceRecord $record) => $record->date->format('Y-m-d'));

        return collect(range(1, $date->daysInMonth))
            ->map(fn (int $day) => $this->formatRow($date->day($day), $recordsByDate));
    }

    /**
     * 表示月が当月以前の場合に、レコードが無い日付分を補完する。
     * 当月は1日〜当日まで、過去月はその月の1日〜末日まで対象。
     * 既に存在する日付は upsert によりスキップされる（重複作成しない）。
     *
     * @param  User  $user  対象ユーザー
     * @param  CarbonImmutable  $startOfMonth  表示対象月の1日
     */
    private function ensureRecordsExist(User $user, CarbonImmutable $startOfMonth): void
    {
        $lastDay = $startOfMonth->isSameMonth(today())
            ? today()->day
            : $startOfMonth->daysInMonth;

        $records = collect(range(1, $lastDay))
            ->map(fn (int $day) => [
                'user_id' => $user->id,
                'date' => $startOfMonth->day($day)->toDateTimeString(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        AttendanceRecord::upsert($records->all(), ['user_id', 'date'], []);
    }

    /**
     *  1日分の勤怠情報を、画面表示用の配列に整形する。
     * レコードが存在しない日は、各項目を空文字・IDをnullとして返す。
     *
     * @param  CarbonImmutable  $day  整形対象の日付
     * @param  Collection<string, AttendanceRecord>  $recordsByDate  日付文字列(Y-m-d)をキーにした勤怠レコードのコレクション
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
