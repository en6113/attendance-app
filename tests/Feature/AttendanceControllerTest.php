<?php

namespace Tests\Feature;

use App\Models\AttendanceCorrectRequest;
use App\Models\AttendanceRecord;
use App\Models\BreakTime;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_自分が行った勤怠情報が全て表示される(): void
    {
        $user = User::factory()->create();
        AttendanceRecord::factory()->for($user)->create([
            'work_date' => '2026-09-05',
            'clock_in_time' => '2026-09-05 09:00:00',
            'clock_out_time' => '2026-09-05 18:00:00',
        ]);

        $response = $this->actingAs($user)->get('/attendance/list?date=2026-09');

        $response->assertOk();
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    public function test_勤怠一覧画面に遷移した際に現在の月が表示される(): void
    {
        $this->travelTo('2026-09-05');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/attendance/list');

        $response->assertSee('2026/09');
    }

    public function test_「前月」を押下した時に表示月の前月の情報が表示される(): void
    {
        $user = User::factory()->create();
        AttendanceRecord::factory()->for($user)->create([
            'work_date' => '2026-08-10',
            'clock_in_time' => '2026-08-10 09:00:00',
        ]);

        $response = $this->actingAs($user)->get('/attendance/list?date=2026-09');
        $response->assertSee('date=2026-08');

        $response = $this->actingAs($user)->get('/attendance/list?date=2026-08');
        $response->assertSee('09:00');
    }

    public function test_「翌月」を押下した時に表示月の翌月の情報が表示される(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/attendance/list?date=2026-09');
        $response->assertSee('date=2026-10');

        $response = $this->actingAs($user)->get('/attendance/list?date=2026-10');
        $response->assertSee('2026/10');
        $response->assertSee('10/01');
    }

    public function test_「詳細」を押下すると、その日の勤怠詳細画面に遷移する(): void
    {
        $user = User::factory()->create();
        $record = AttendanceRecord::factory()->for($user)->create([
            'work_date' => '2026-09-05',
        ]);

        $response = $this->actingAs($user)->get('/attendance/list?date=2026-09');

        $response->assertSee('/attendance/detail/'.$record->id);
    }

    public function test_本人は自分の勤怠詳細画面を閲覧でき、名前・日付・出退勤時刻・休憩時刻が一致している(): void
    {
        $user = User::factory()->create(['name' => '山田太郎']);
        $record = AttendanceRecord::factory()->for($user)->create([
            'work_date' => '2026-09-05',
            'clock_in_time' => '2026-09-05 09:00:00',
            'clock_out_time' => '2026-09-05 18:00:00',
        ]);
        BreakTime::factory()->for($record)->create([
            'break_start_time' => '2026-09-05 12:00:00',
            'break_end_time' => '2026-09-05 13:00:00',
        ]);

        $response = $this->actingAs($user)->get('/attendance/detail/'.$record->id);

        $response->assertOk();
        $response->assertSee('山田太郎');
        $response->assertSee('9月5日');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('12:00');
        $response->assertSee('13:00');
    }

    public function test_管理者以外は他人の勤怠詳細画面は閲覧できない(): void
    {
        $user = User::factory()->create();
        $record = AttendanceRecord::factory()->create();

        $response = $this->actingAs($user)->get('/attendance/detail/'.$record->id);

        $response->assertForbidden();
    }

    public function test_承認待ちの申請がない場合は修正フォームが表示される(): void
    {
        $user = User::factory()->create();
        $record = AttendanceRecord::factory()->for($user)->create();

        $response = $this->actingAs($user)->get('/attendance/detail/'.$record->id);

        $response->assertSee('修正');
        $response->assertDontSee('承認待ちのため修正できません');
    }

    public function test_承認待ちの場合、詳細画面に申請中の内容が表示される(): void
    {
        $user = User::factory()->create();
        $record = AttendanceRecord::factory()->for($user)->create([
            'work_date' => '2026-09-05',
            'clock_in_time' => '2026-09-05 09:00:00',
            'clock_out_time' => '2026-09-05 18:00:00',
        ]);
        AttendanceCorrectRequest::factory()->for($record)->create([
            'new_date' => '2026-09-05',
            'new_clock_in' => '09:30',
            'new_clock_out' => '19:00',
        ]);

        $response = $this->actingAs($user)->get('/attendance/detail/'.$record->id);

        $response->assertSee('09:30');
        $response->assertSee('19:00');
        $response->assertSee('承認待ちのため修正できません');
    }

    public function test_本人は修正申請を送信できる(): void
    {
        $user = User::factory()->create();
        $record = AttendanceRecord::factory()->for($user)->create();

        $response = $this->actingAs($user)->post('/attendance/detail/'.$record->id, [
            'new_clock_in' => '09:00',
            'new_clock_out' => '18:00',
            'new_break_in' => ['12:00'],
            'new_break_out' => ['13:00'],
            'comment' => '電車遅延のため',
        ]);

        $response->assertRedirect('/attendance/detail/'.$record->id);
        $this->assertDatabaseHas('attendance_correct_requests', [
            'attendance_record_id' => $record->id,
            'new_clock_in' => '09:00',
            'new_clock_out' => '18:00',
            'comment' => '電車遅延のため',
        ]);
    }

    public function test_休憩記録の修正内容も保存される(): void
    {
        $user = User::factory()->create();
        $record = AttendanceRecord::factory()->for($user)->create();

        $this->actingAs($user)->post('/attendance/detail/'.$record->id, [
            'new_clock_in' => '09:00',
            'new_clock_out' => '18:00',
            'new_break_in' => ['12:00'],
            'new_break_out' => ['13:00'],
            'comment' => '備考',
        ]);

        $correctRequest = AttendanceCorrectRequest::first();

        $this->assertDatabaseHas('proposal_breaks', [
            'attendance_correct_request_id' => $correctRequest->id,
            'break_in' => '12:00',
            'break_out' => '13:00',
        ]);
    }

    public function test_他人の勤怠には修正申請を送信できない(): void
    {
        $user = User::factory()->create();
        $record = AttendanceRecord::factory()->create();

        $response = $this->actingAs($user)->post('/attendance/detail/'.$record->id, [
            'new_clock_in' => '09:00',
            'new_clock_out' => '18:00',
            'new_break_in' => [],
            'new_break_out' => [],
            'comment' => '備考',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('attendance_correct_requests', 0);
    }

    public function test_バリデーションエラーの場合は修正申請が保存されない(): void
    {
        $user = User::factory()->create();
        $record = AttendanceRecord::factory()->for($user)->create();

        $response = $this->actingAs($user)->post('/attendance/detail/'.$record->id, [
            'new_clock_in' => '18:00',
            'new_clock_out' => '09:00',
            'new_break_in' => [],
            'new_break_out' => [],
            'comment' => '備考',
        ]);

        $response->assertSessionHasErrors(['new_clock_out' => '出勤時間もしくは退勤時間が不適切な値です']);
        $this->assertDatabaseCount('attendance_correct_requests', 0);
    }
}
