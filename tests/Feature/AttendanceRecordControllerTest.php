<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\BreakTime;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceRecordControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_勤怠打刻画面に表示される日時が現在の日時と一致する(): void
    {
        $now = now();
        $this->travelTo($now);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertSee($now->isoFormat('YYYY年MM月DD日(ddd)'));
        $response->assertSee($now->format('H:i'));
    }

    public function test_勤務外の場合、勤怠ステータスが正しく表示される(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertSee('勤務外');
    }

    public function test_出勤中の場合、勤怠ステータスが正しく表示される(): void
    {
        $user = User::factory()->create();
        AttendanceRecord::factory()->for($user)->create();

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertSee('出勤中');
    }

    public function test_休憩中の場合、勤怠ステータスが正しく表示される(): void
    {
        $user = User::factory()->create();
        $record = AttendanceRecord::factory()->for($user)->create();
        BreakTime::factory()->for($record)->create();

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertSee('休憩中');
    }

    public function test_退勤済の場合、勤怠ステータスが正しく表示される(): void
    {
        $user = User::factory()->create();
        AttendanceRecord::factory()->for($user)->create(['clock_out_time' => now()]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertSee('退勤済');
    }

    public function test_出勤ボタンが正しく機能する(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertSee('出勤');

        $this->actingAs($user)->post('/attendance', ['action' => 'clock_in']);

        $this->assertSame('出勤中', $user->fresh()->attendance_status);
    }

    public function test_出勤は一日一回のみできる(): void
    {
        $user = User::factory()->create();
        AttendanceRecord::factory()->for($user)->create(['clock_out_time' => now()]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertDontSee('出勤');
    }

    public function test_出勤時刻が勤怠一覧画面で確認できる(): void
    {
        $this->travelTo(today()->setTime(9, 0));
        $user = User::factory()->create();

        $this->actingAs($user)->post('/attendance', ['action' => 'clock_in']);

        $response = $this->actingAs($user)->get('/attendance/list');

        $response->assertSee('09:00');
    }

    public function test_休憩入ボタンが正しく機能する(): void
    {
        $user = User::factory()->create();
        AttendanceRecord::factory()->for($user)->create();

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertSee('休憩入');

        $this->actingAs($user)->post('/attendance', ['action' => 'break_in']);

        $this->assertSame('休憩中', $user->fresh()->attendance_status);
    }

    public function test_休憩は一日に何回でもできる(): void
    {
        $user = User::factory()->create();
        AttendanceRecord::factory()->for($user)->create();

        $this->actingAs($user)->post('/attendance', ['action' => 'break_in']);
        $this->actingAs($user)->post('/attendance', ['action' => 'break_out']);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertSee('休憩入');
    }

    public function test_休憩戻ボタンが正しく機能する(): void
    {
        $user = User::factory()->create();
        $record = AttendanceRecord::factory()->for($user)->create();
        BreakTime::factory()->for($record)->create();

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertSee('休憩戻');

        $this->actingAs($user)->post('/attendance', ['action' => 'break_out']);

        $this->assertSame('出勤中', $user->fresh()->attendance_status);
    }

    public function test_休憩戻は一日に何回でもできる(): void
    {
        $user = User::factory()->create();
        AttendanceRecord::factory()->for($user)->create();

        $this->actingAs($user)->post('/attendance', ['action' => 'break_in']);
        $this->actingAs($user)->post('/attendance', ['action' => 'break_out']);
        $this->actingAs($user)->post('/attendance', ['action' => 'break_in']);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertSee('休憩戻');
    }

    public function test_休憩時間合計が勤怠一覧画面で確認できる(): void
    {
        $this->travelTo(today()->setTime(9, 0));
        $user = User::factory()->create();
        $this->actingAs($user)->post('/attendance', ['action' => 'clock_in']);

        $this->travelTo(today()->setTime(12, 0));
        $this->actingAs($user)->post('/attendance', ['action' => 'break_in']);

        $this->travelTo(today()->setTime(13, 0));
        $this->actingAs($user)->post('/attendance', ['action' => 'break_out']);

        $response = $this->actingAs($user)->get('/attendance/list');

        $response->assertSee('1:00');
    }

    public function test_退勤ボタンが正しく機能する(): void
    {
        $user = User::factory()->create();
        AttendanceRecord::factory()->for($user)->create();

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertSee('退勤');

        $this->actingAs($user)->post('/attendance', ['action' => 'clock_out']);

        $this->assertSame('退勤済', $user->fresh()->attendance_status);
    }

    public function test_退勤時刻が勤怠一覧画面で確認できる(): void
    {
        $this->travelTo(today()->setTime(9, 0));
        $user = User::factory()->create();
        $this->actingAs($user)->post('/attendance', ['action' => 'clock_in']);

        $this->travelTo(today()->setTime(18, 0));
        $this->actingAs($user)->post('/attendance', ['action' => 'clock_out']);

        $response = $this->actingAs($user)->get('/attendance/list');

        $response->assertSee('18:00');
    }

    public function test_現在のステータスと矛盾するactionを送るとエラーになる(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/attendance', ['action' => 'clock_out']);

        $response->assertSessionHasErrors(['action' => '現在のステータスでは、その操作はできません。']);
        $this->assertDatabaseCount('attendance_records', 0);
    }
}
