<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\BreakTime;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAttendanceControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_その日になされた全ユーザーの勤怠情報が正確に確認できる(): void
    {
        $admin = User::factory()->create(['admin_status' => true]);
        $user = User::factory()->create(['name' => '山田太郎']);
        AttendanceRecord::factory()->for($user)->create([
            'date' => '2026-09-05',
            'clock_in_time' => '2026-09-05 09:00:00',
            'clock_out_time' => '2026-09-05 18:00:00',
        ]);

        $response = $this->actingAs($admin)->get('/admin/attendance/list?date=2026-09-05');

        $response->assertOk();
        $response->assertSee('山田太郎');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    public function test_勤怠一覧画面に遷移した際に現在の日付が表示される(): void
    {
        $this->travelTo('2026-09-05');
        $admin = User::factory()->create(['admin_status' => true]);

        $response = $this->actingAs($admin)->get('/admin/attendance/list');

        $response->assertSee('2026/09/05');
    }

    public function test_「前日」を押下した時に前の日の勤怠情報が表示される(): void
    {
        $admin = User::factory()->create(['admin_status' => true]);
        $user = User::factory()->create(['name' => '山田太郎']);
        AttendanceRecord::factory()->for($user)->create([
            'date' => '2026-09-04',
            'clock_in_time' => '2026-09-04 09:00:00',
        ]);

        $response = $this->actingAs($admin)->get('/admin/attendance/list?date=2026-09-05');
        $response->assertSee('date=2026-09-04');

        $response = $this->actingAs($admin)->get('/admin/attendance/list?date=2026-09-04');
        $response->assertSee('09:00');
    }

    public function test_「翌日」を押下した時に次の日の勤怠情報が表示される(): void
    {
        $admin = User::factory()->create(['admin_status' => true]);
        $user = User::factory()->create(['name' => '山田太郎']);
        AttendanceRecord::factory()->for($user)->create([
            'date' => '2026-09-06',
            'clock_in_time' => '2026-09-06 09:00:00',
        ]);

        $response = $this->actingAs($admin)->get('/admin/attendance/list?date=2026-09-05');
        $response->assertSee('date=2026-09-06');

        $response = $this->actingAs($admin)->get('/admin/attendance/list?date=2026-09-06');
        $response->assertSee('09:00');
    }

    public function test_勤怠詳細画面に表示されるデータが選択したものになっている(): void
    {
        $admin = User::factory()->create(['admin_status' => true]);
        $user = User::factory()->create(['name' => '山田太郎']);
        $record = AttendanceRecord::factory()->for($user)->create([
            'date' => '2026-09-05',
            'clock_in_time' => '2026-09-05 09:00:00',
            'clock_out_time' => '2026-09-05 18:00:00',
        ]);
        BreakTime::factory()->for($record)->create([
            'break_start_time' => '2026-09-05 12:00:00',
            'break_end_time' => '2026-09-05 13:00:00',
        ]);

        $response = $this->actingAs($admin)->get('/admin/attendance/'.$record->id);

        $response->assertOk();
        $response->assertSee('山田太郎');
        $response->assertSee('9月5日');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('12:00');
        $response->assertSee('13:00');
    }

    public function test_出勤時間が退勤時間より後になっている場合エラーメッセージが表示される(): void
    {
        $admin = User::factory()->create(['admin_status' => true]);
        $record = AttendanceRecord::factory()->create();

        $response = $this->actingAs($admin)->post('/admin/attendance/'.$record->id, [
            'new_clock_in' => '18:00',
            'new_clock_out' => '09:00',
            'new_break_in' => [],
            'new_break_out' => [],
            'comment' => '備考',
        ]);

        $response->assertSessionHasErrors(['new_clock_out' => '出勤時間もしくは退勤時間が不適切な値です']);
    }

    public function test_休憩開始時間が退勤時間より後になっている場合エラーメッセージが表示される(): void
    {
        $admin = User::factory()->create(['admin_status' => true]);
        $record = AttendanceRecord::factory()->create();

        $response = $this->actingAs($admin)->post('/admin/attendance/'.$record->id, [
            'new_clock_in' => '09:00',
            'new_clock_out' => '18:00',
            'new_break_in' => ['19:00'],
            'new_break_out' => ['19:30'],
            'comment' => '備考',
        ]);

        $response->assertSessionHasErrors(['new_break_in.0' => '休憩時間が不適切な値です']);
    }

    public function test_休憩終了時間が退勤時間より後になっている場合エラーメッセージが表示される(): void
    {
        $admin = User::factory()->create(['admin_status' => true]);
        $record = AttendanceRecord::factory()->create();

        $response = $this->actingAs($admin)->post('/admin/attendance/'.$record->id, [
            'new_clock_in' => '09:00',
            'new_clock_out' => '18:00',
            'new_break_in' => ['17:00'],
            'new_break_out' => ['19:00'],
            'comment' => '備考',
        ]);

        $response->assertSessionHasErrors(['new_break_out.0' => '休憩時間もしくは退勤時間が不適切な値です']);
    }

    public function test_備考欄が未入力の場合エラーメッセージが表示される(): void
    {
        $admin = User::factory()->create(['admin_status' => true]);
        $record = AttendanceRecord::factory()->create();

        $response = $this->actingAs($admin)->post('/admin/attendance/'.$record->id, [
            'new_clock_in' => '09:00',
            'new_clock_out' => '18:00',
            'new_break_in' => [],
            'new_break_out' => [],
            'comment' => '',
        ]);

        $response->assertSessionHasErrors(['comment' => '備考を記入してください']);
    }

    public function test_一般ユーザーは管理者の勤怠一覧にアクセスできない(): void
    {
        $user = User::factory()->create(['admin_status' => false]);

        $response = $this->actingAs($user)->get('/admin/attendance/list');

        $response->assertForbidden();
    }
}
