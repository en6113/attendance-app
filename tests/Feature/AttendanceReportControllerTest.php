<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\BreakTime;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceReportControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_ゲストはレポートページにアクセスできない(): void
    {
        $response = $this->get('/attendance/report');

        $response->assertRedirect('/login');
    }

    public function test_認証ユーザーの統計情報（サマリーと月次推移、異常検知は別途テスト）が正しく計算される(): void
    {
        $this->travelTo('2026-09-15');
        $user = User::factory()->create();

        $record1 = AttendanceRecord::factory()->for($user)->create([
            'date' => '2026-09-01',
            'clock_in_time' => '2026-09-01 09:00:00',
            'clock_out_time' => '2026-09-01 18:00:00',
        ]);
        BreakTime::factory()->for($record1)->create([
            'break_start_time' => '2026-09-01 12:00:00',
            'break_end_time' => '2026-09-01 13:00:00',
        ]);

        $record2 = AttendanceRecord::factory()->for($user)->create([
            'date' => '2026-09-02',
            'clock_in_time' => '2026-09-02 09:00:00',
            'clock_out_time' => '2026-09-02 20:00:00',
        ]);
        BreakTime::factory()->for($record2)->create([
            'break_start_time' => '2026-09-02 12:00:00',
            'break_end_time' => '2026-09-02 13:00:00',
        ]);

        $record3 = AttendanceRecord::factory()->for($user)->create([
            'date' => '2026-08-10',
            'clock_in_time' => '2026-08-10 09:00:00',
            'clock_out_time' => '2026-08-10 18:00:00',
        ]);
        BreakTime::factory()->for($record3)->create([
            'break_start_time' => '2026-08-10 12:00:00',
            'break_end_time' => '2026-08-10 13:00:00',
        ]);

        $response = $this->actingAs($user)->get('/attendance/report');

        $response->assertOk();
        $response->assertViewHas('summary', [
            'total_work_minutes' => 1560, // 8h + 10h + 8h
            'total_overtime_minutes' => 120, // 10hの日だけ8h超過分の2h
            'avg_work_minutes' => 520, // 1560分 / 3日
        ]);

        $monthlyTrend = $response->viewData('monthlyTrend');
        $this->assertSame(480, $monthlyTrend->firstWhere('month', '2026/08')['work_minutes']); // 8h
        $this->assertSame(1080, $monthlyTrend->firstWhere('month', '2026/09')['work_minutes']); // 18h
        $this->assertSame(0, $monthlyTrend->firstWhere('month', '2026/04')['work_minutes']); // データがない月は０として表示
    }

    public function test_勤怠記録がないユーザーで安全に処理される(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/attendance/report');

        $response->assertOk();
        $response->assertViewHas('summary', [
            'total_work_minutes' => 0,
            'total_overtime_minutes' => 0,
            'avg_work_minutes' => 0,
        ]);
        $response->assertViewHas('anomalies', [
            'late_count' => 0,
            'early_leave_count' => 0,
            'long_work_count' => 0,
        ]);
    }

    public function test_遅刻_早退_長時間労働が当月の異常検知として集計される(): void
    {
        $this->travelTo('2026-09-15');
        $user = User::factory()->create();

        $lateRecord = AttendanceRecord::factory()->for($user)->create([
            'date' => '2026-09-01',
            'clock_in_time' => '2026-09-01 09:30:00', // 遅刻
            'clock_out_time' => '2026-09-01 18:00:00',
        ]);
        BreakTime::factory()->for($lateRecord)->create([
            'break_start_time' => '2026-09-01 12:00:00',
            'break_end_time' => '2026-09-01 13:00:00',
        ]);

        $earlyLeaveRecord = AttendanceRecord::factory()->for($user)->create([
            'date' => '2026-09-02',
            'clock_in_time' => '2026-09-02 09:00:00',
            'clock_out_time' => '2026-09-02 17:00:00', // 早退
        ]);
        BreakTime::factory()->for($earlyLeaveRecord)->create([
            'break_start_time' => '2026-09-02 12:00:00',
            'break_end_time' => '2026-09-02 13:00:00',
        ]);

        $longWorkRecord = AttendanceRecord::factory()->for($user)->create([
            'date' => '2026-09-03',
            'clock_in_time' => '2026-09-03 08:00:00',
            'clock_out_time' => '2026-09-03 21:00:00', // 長時間労働
        ]);
        BreakTime::factory()->for($longWorkRecord)->create([
            'break_start_time' => '2026-09-03 12:00:00',
            'break_end_time' => '2026-09-03 13:00:00',
        ]);

        $response = $this->actingAs($user)->get('/attendance/report');

        $response->assertViewHas('anomalies', [
            'late_count' => 1,
            'early_leave_count' => 1,
            'long_work_count' => 1,
        ]);
    }

    public function test_始業9時_終業20時_労働10時間ちょうどは異常にカウントされない(): void
    {
        $this->travelTo('2026-09-15');
        $user = User::factory()->create();

        $record = AttendanceRecord::factory()->for($user)->create([
            'date' => '2026-09-01',
            'clock_in_time' => '2026-09-01 09:00:00',
            'clock_out_time' => '2026-09-01 20:00:00',
        ]);
        BreakTime::factory()->for($record)->create([
            'break_start_time' => '2026-09-01 12:00:00',
            'break_end_time' => '2026-09-01 13:00:00',
        ]);

        $response = $this->actingAs($user)->get('/attendance/report');

        $response->assertViewHas('anomalies', [
            'late_count' => 0,
            'early_leave_count' => 0,
            'long_work_count' => 0,
        ]);
    }

    public function test_他ユーザーの勤怠情報は集計に含まれない(): void
    {
        $this->travelTo('2026-09-15');
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $otherRecord = AttendanceRecord::factory()->for($otherUser)->create([
            'date' => '2026-09-01',
            'clock_in_time' => '2026-09-01 09:00:00',
            'clock_out_time' => '2026-09-01 18:00:00',
        ]);
        BreakTime::factory()->for($otherRecord)->create([
            'break_start_time' => '2026-09-01 12:00:00',
            'break_end_time' => '2026-09-01 13:00:00',
        ]);

        $response = $this->actingAs($user)->get('/attendance/report');

        $response->assertViewHas('summary', [
            'total_work_minutes' => 0,
            'total_overtime_minutes' => 0,
            'avg_work_minutes' => 0,
        ]);
    }

    public function test_集計期間より前のデータは集計対象に含まれない(): void
    {
        $this->travelTo('2026-09-15');
        $user = User::factory()->create();

        $record = AttendanceRecord::factory()->for($user)->create([
            'date' => '2026-02-10',
            'clock_in_time' => '2026-02-10 09:00:00',
            'clock_out_time' => '2026-02-10 18:00:00',
        ]);
        BreakTime::factory()->for($record)->create([
            'break_start_time' => '2026-02-10 12:00:00',
            'break_end_time' => '2026-02-10 13:00:00',
        ]);

        $response = $this->actingAs($user)->get('/attendance/report');

        $response->assertViewHas('summary', [
            'total_work_minutes' => 0,
            'total_overtime_minutes' => 0,
            'avg_work_minutes' => 0,
        ]);
    }
}
