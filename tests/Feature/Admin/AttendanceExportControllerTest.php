<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\BreakTime;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceExportControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_管理者はスタッフの月次勤怠一覧を_cs_vでダウンロードできる(): void
    {
        $admin = User::factory()->create(['admin_status' => true]);
        $staff = User::factory()->create(['name' => '山田太郎']);
        $record = AttendanceRecord::factory()->for($staff)->create([
            'date' => '2026-09-05',
            'clock_in_time' => '2026-09-05 09:00:00',
            'clock_out_time' => '2026-09-05 18:00:00',
        ]);
        BreakTime::factory()->for($record, 'attendanceRecord')->create([
            'break_start_time' => '2026-09-05 12:00:00',
            'break_end_time' => '2026-09-05 13:00:00',
        ]);

        $response = $this->actingAs($admin)->post('/export', [
            'user_id' => $staff->id,
            'year_month' => '2026-09',
        ]);

        $response->assertOk();
        $response->assertDownload();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();
        $this->assertStringContainsString('日付,出勤,退勤,休憩,合計', $content);
        $this->assertStringContainsString('09/05', $content);
        $this->assertStringContainsString('09:00', $content);
        $this->assertStringContainsString('18:00', $content);
        $this->assertStringContainsString('1:00', $content);
        $this->assertStringContainsString('8:00', $content);
    }

    public function test_一般ユーザーは_cs_v出力にアクセスできない(): void
    {
        $user = User::factory()->create(['admin_status' => false]);
        $staff = User::factory()->create();

        $response = $this->actingAs($user)->post('/export', [
            'user_id' => $staff->id,
            'year_month' => '2026-09',
        ]);

        $response->assertForbidden();
    }

    public function test_存在しないuser_idを指定した場合バリデーションエラーになる(): void
    {
        $admin = User::factory()->create(['admin_status' => true]);

        $response = $this->actingAs($admin)->post('/export', [
            'user_id' => 9999,
            'year_month' => '2026-09',
        ]);

        $response->assertSessionHasErrors('user_id');
    }

    public function test_不正な形式のyear_monthを指定した場合バリデーションエラーになる(): void
    {
        $admin = User::factory()->create(['admin_status' => true]);
        $staff = User::factory()->create();

        $response = $this->actingAs($admin)->post('/export', [
            'user_id' => $staff->id,
            'year_month' => 'abc',
        ]);

        $response->assertSessionHasErrors('year_month');
    }
}
