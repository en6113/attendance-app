<?php

namespace Database\Seeders;

use App\Models\AttendanceRecord;
use App\Models\BreakTime;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class AttendanceRecordSeeder extends Seeder
{
    public function run(): void
    {
        $user1 = User::where('email', 'user1@example.com')->firstOrFail();
        $this->seedUser1($user1);

        User::where('email', '!=', 'user1@example.com')->get()
            ->each(fn (User $user) => $this->seedRandomAttendance($user));
    }

    /**
     * user1には要件シート記載の意図的なデータを投入する。
     */
    private function seedUser1(User $user): void
    {
        $currentMonthStart = today()->toImmutable()->startOfMonth();

        // 過去5ヶ月: 各月 平日15日、通常勤務(9:00-18:00)
        for ($i = 5; $i >= 1; $i--) {
            $monthStart = $currentMonthStart->subMonths($i);

            foreach ($this->weekdaysOfMonth($monthStart, 15) as $date) {
                $this->createRecordWithLunchBreak($user, $date, '09:00', '18:00');
            }
        }

        // 当月17日: 通常10 / 残業3 / 遅刻2 / 早退1 / 長時間労働1
        $patterns = collect([
            ...array_fill(0, 10, ['09:00', '18:00']),
            ...array_fill(0, 3, ['09:00', '20:00']),
            ...array_fill(0, 2, ['09:30', '18:00']),
            ...array_fill(0, 1, ['09:00', '17:00']),
            ...array_fill(0, 1, ['08:00', '21:00']),
        ])->shuffle()->values();

        $weekdaysThisMonth = $this->weekdaysOfMonth($currentMonthStart, 17);

        foreach ($weekdaysThisMonth as $index => $date) {
            [$clockIn, $clockOut] = $patterns[$index];
            $this->createRecordWithLunchBreak($user, $date, $clockIn, $clockOut);
        }
    }

    /**
     * 他ユーザーには実運用に近いランダムなデータを投入する。
     */
    private function seedRandomAttendance(User $user): void
    {
        $currentMonthStart = today()->toImmutable()->startOfMonth();

        for ($i = 5; $i >= 0; $i--) {
            $monthStart = $currentMonthStart->subMonths($i);
            $weekdayCount = fake()->numberBetween(12, 18);

            foreach ($this->weekdaysOfMonth($monthStart, $weekdayCount) as $date) {
                $clockIn = $date->setTimeFromTimeString('09:00')->subMinutes(fake()->numberBetween(0, 15));
                $clockOut = $date->setTimeFromTimeString('18:00')->addMinutes(fake()->numberBetween(0, 30));

                $this->createRecordWithLunchBreak($user, $date, $clockIn->format('H:i'), $clockOut->format('H:i'));
            }
        }
    }

    /**
     * 指定した月の平日の日付を、先頭から$count件取得する。
     *
     * @return array<int, CarbonImmutable>
     */
    private function weekdaysOfMonth(CarbonImmutable $monthStart, int $count): array
    {
        return collect(range(0, $monthStart->daysInMonth - 1))
            ->map(fn (int $offset) => $monthStart->addDays($offset))
            ->filter(fn (CarbonImmutable $date) => $date->isWeekday())
            ->take($count)
            ->values()
            ->all();
    }

    private function createRecordWithLunchBreak(
        User $user,
        CarbonImmutable $date,
        string $clockIn,
        string $clockOut
    ): void {
        $record = AttendanceRecord::create([
            'user_id' => $user->id,
            'work_date' => $date,
            'clock_in_time' => $date->setTimeFromTimeString($clockIn),
            'clock_out_time' => $date->setTimeFromTimeString($clockOut),
        ]);

        BreakTime::create([
            'attendance_record_id' => $record->id,
            'break_start_time' => $date->setTimeFromTimeString('12:00'),
            'break_end_time' => $date->setTimeFromTimeString('13:00'),
        ]);
    }
}
