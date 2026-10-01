<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_管理者ユーザーが全一般ユーザーの「氏名」「メールアドレス」を確認できる(): void
    {
        $admin = User::factory()->create(['admin_status' => true]);
        $user = User::factory()->create([
            'admin_status' => false,
            'name' => '山田太郎',
            'email' => 'yamada@example.com',
        ]);

        $response = $this->actingAs($admin)->get('/admin/staff/list');

        $response->assertOk();
        $response->assertSee('山田太郎');
        $response->assertSee('yamada@example.com');
    }

    public function test_管理者ユーザーはスタッフ一覧に表示されない(): void
    {
        $admin = User::factory()->create([
            'admin_status' => true,
            'name' => '管理者太郎',
        ]);

        $response = $this->actingAs($admin)->get('/admin/staff/list');

        $response->assertDontSee('管理者太郎');
    }

    public function test_選択したユーザーの勤怠情報が正しく表示される(): void
    {
        $admin = User::factory()->create(['admin_status' => true]);
        $staff = User::factory()->create(['name' => '山田太郎']);
        AttendanceRecord::factory()->for($staff)->create([
            'date' => '2026-09-05',
            'clock_in_time' => '2026-09-05 09:00:00',
            'clock_out_time' => '2026-09-05 18:00:00',
        ]);

        $response = $this->actingAs($admin)->get('/admin/attendance/staff/'.$staff->id.'?date=2026-09');

        $response->assertOk();
        $response->assertSee('山田太郎');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    public function test_「前月」を押下した時に表示月の前月の情報が表示される(): void
    {
        $admin = User::factory()->create(['admin_status' => true]);
        $staff = User::factory()->create();
        AttendanceRecord::factory()->for($staff)->create([
            'date' => '2026-08-10',
            'clock_in_time' => '2026-08-10 09:00:00',
        ]);

        $response = $this->actingAs($admin)->get('/admin/attendance/staff/'.$staff->id.'?date=2026-09');
        $response->assertSee('date=2026-08');

        $response = $this->actingAs($admin)->get('/admin/attendance/staff/'.$staff->id.'?date=2026-08');
        $response->assertSee('09:00');
    }

    public function test_「翌月」を押下した時に表示月の翌月の情報が表示される(): void
    {
        $admin = User::factory()->create(['admin_status' => true]);
        $staff = User::factory()->create();

        $response = $this->actingAs($admin)->get('/admin/attendance/staff/'.$staff->id.'?date=2026-09');

        $response->assertSee('date=2026-10');
    }

    public function test_不正な形式のdateパラメータの場合バリデーションエラーになる(): void
    {
        $admin = User::factory()->create(['admin_status' => true]);
        $staff = User::factory()->create();

        $response = $this->actingAs($admin)->get('/admin/attendance/staff/'.$staff->id.'?date=abc');

        $response->assertSessionHasErrors('date');
    }

    public function test_詳細を押下するとその日の勤怠詳細画面に遷移する(): void
    {
        $admin = User::factory()->create(['admin_status' => true]);
        $staff = User::factory()->create();
        $record = AttendanceRecord::factory()->for($staff)->create([
            'date' => '2026-09-05',
        ]);

        $response = $this->actingAs($admin)->get('/admin/attendance/staff/'.$staff->id.'?date=2026-09');

        $response->assertSee('/admin/attendance/'.$record->id);
    }

    public function test_過去月の表示で勤怠情報がない日にも詳細ボタンが表示される(): void
    {
        $this->travelTo('2026-09-05');
        $admin = User::factory()->create(['admin_status' => true]);
        $staff = User::factory()->create();

        $response = $this->actingAs($admin)->get('/admin/attendance/staff/'.$staff->id.'?date=2026-08');

        $record = AttendanceRecord::where('user_id', $staff->id)
            ->whereDate('date', '2026-08-05')
            ->first();

        $response->assertOk();
        $this->assertNotNull($record);
        $response->assertSee('/admin/attendance/'.$record->id);
    }

    public function test_当月の表示で本日より後の日には詳細ボタンが表示されない(): void
    {
        $this->travelTo('2026-09-10');
        $admin = User::factory()->create(['admin_status' => true]);
        $staff = User::factory()->create();

        $response = $this->actingAs($admin)->get('/admin/attendance/staff/'.$staff->id.'?date=2026-09');

        $response->assertOk();
        $this->assertDatabaseMissing('attendance_records', [
            'user_id' => $staff->id,
            'date' => '2026-09-15',
        ]);
    }

    public function test_未打刻日の詳細を管理者が開いても出勤退勤欄が空のまま正常に表示される(): void
    {
        $this->travelTo('2026-09-05');
        $admin = User::factory()->create(['admin_status' => true]);
        $staff = User::factory()->create(['name' => '山田太郎']);

        $this->actingAs($admin)->get('/admin/attendance/staff/'.$staff->id.'?date=2026-09');
        $record = AttendanceRecord::where('user_id', $staff->id)
            ->whereDate('date', '2026-09-01')
            ->first();

        $response = $this->actingAs($admin)->get('/admin/attendance/'.$record->id);

        $response->assertOk();
        $response->assertSee('山田太郎');
        $response->assertSee('9月1日');
    }

    public function test_一般ユーザーはスタッフ一覧にアクセスできない(): void
    {
        $user = User::factory()->create(['admin_status' => false]);

        $response = $this->actingAs($user)->get('/admin/staff/list');

        $response->assertForbidden();
    }

    public function test_一般ユーザーはスタッフの月次勤怠一覧にアクセスできない(): void
    {
        $user = User::factory()->create(['admin_status' => false]);
        $staff = User::factory()->create();

        $response = $this->actingAs($user)->get('/admin/attendance/staff/'.$staff->id);

        $response->assertForbidden();
    }
}
