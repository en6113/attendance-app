<?php

namespace Tests\Feature;

use App\Models\AttendanceCorrectRequest;
use App\Models\AttendanceRecord;
use App\Models\ProposalBreak;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminApplicationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_修正申請の詳細内容が正しく表示されている(): void
    {
        $admin = User::factory()->create(['admin_status' => true]);
        $user = User::factory()->create(['name' => '山田太郎']);
        $record = AttendanceRecord::factory()->for($user)->create();
        $correctRequest = AttendanceCorrectRequest::factory()->for($record)->create([
            'new_date' => '2026-09-05',
            'new_clock_in' => '11:00',
            'new_clock_out' => '18:00',
            'comment' => '年休（9:00-11:00）',
        ]);
        ProposalBreak::factory()->for($correctRequest)->create([
            'break_in' => '12:00',
            'break_out' => '13:00',
        ]);

        $response = $this->actingAs($admin)->get('/stamp_correction_request/approve/'.$correctRequest->id);

        $response->assertOk();
        $response->assertSee('山田太郎');
        $response->assertSee('9月5日');
        $response->assertSee('11:00');
        $response->assertSee('18:00');
        $response->assertSee('12:00');
        $response->assertSee('13:00');
        $response->assertSee('年休（9:00-11:00）');
    }

    public function test_修正申請の承認処理が正しく行われる(): void
    {
        $admin = User::factory()->create(['admin_status' => true]);
        $user = User::factory()->create();
        $record = AttendanceRecord::factory()->for($user)->create([
            'date' => '2026-09-05',
            'clock_in_time' => '2026-09-05 09:00:00',
            'clock_out_time' => '2026-09-05 18:00:00',
        ]);
        $correctRequest = AttendanceCorrectRequest::factory()->for($record)->create([
            'new_date' => '2026-09-05',
            'new_clock_in' => '09:30',
            'new_clock_out' => '19:00',
            'comment' => '電車遅延のため',
        ]);
        ProposalBreak::factory()->for($correctRequest)->create([
            'break_in' => '12:00',
            'break_out' => '13:00',
        ]);

        $response = $this->actingAs($admin)->post('/stamp_correction_request/approve/'.$correctRequest->id);

        $response->assertRedirect('/stamp_correction_request/list');
        $this->assertNotNull($correctRequest->fresh()->approved_at);
        $this->assertDatabaseHas('attendance_records', [
            'id' => $record->id,
            'clock_in_time' => '2026-09-05 09:30:00',
            'clock_out_time' => '2026-09-05 19:00:00',
            'comment' => '電車遅延のため',
        ]);
        $this->assertDatabaseHas('breaks', [
            'attendance_record_id' => $record->id,
            'break_start_time' => '2026-09-05 12:00:00',
            'break_end_time' => '2026-09-05 13:00:00',
        ]);
    }

    public function test_承認時に上書き前の勤怠記録が履歴として保存される(): void
    {
        $admin = User::factory()->create(['admin_status' => true]);
        $user = User::factory()->create();
        $record = AttendanceRecord::factory()->for($user)->create([
            'date' => '2026-09-05',
            'clock_in_time' => '2026-09-05 09:00:00',
            'clock_out_time' => '2026-09-05 18:00:00',
        ]);
        $correctRequest = AttendanceCorrectRequest::factory()->for($record)->create([
            'new_date' => '2026-09-05',
            'new_clock_in' => '09:30',
            'new_clock_out' => '19:00',
        ]);

        $this->actingAs($admin)->post('/stamp_correction_request/approve/'.$correctRequest->id);

        $this->assertDatabaseHas('attendance_record_histories', [
            'attendance_record_id' => $record->id,
            'attendance_correct_request_id' => $correctRequest->id,
            'clock_in_time' => '2026-09-05 09:00:00',
            'clock_out_time' => '2026-09-05 18:00:00',
        ]);
    }

    public function test_一般ユーザーは修正申請承認画面にアクセスできない(): void
    {
        $user = User::factory()->create(['admin_status' => false]);
        $correctRequest = AttendanceCorrectRequest::factory()->create();

        $response = $this->actingAs($user)->get('/stamp_correction_request/approve/'.$correctRequest->id);

        $response->assertForbidden();
    }

    public function test_一般ユーザーは修正申請を承認できない(): void
    {
        $user = User::factory()->create(['admin_status' => false]);
        $correctRequest = AttendanceCorrectRequest::factory()->create();

        $response = $this->actingAs($user)->post('/stamp_correction_request/approve/'.$correctRequest->id);

        $response->assertForbidden();
    }
}
