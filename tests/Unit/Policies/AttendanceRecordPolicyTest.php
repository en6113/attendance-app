<?php

namespace Tests\Unit\Policies;

use App\Models\AttendanceRecord;
use App\Models\User;
use App\Policies\AttendanceRecordPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceRecordPolicyTest extends TestCase
{
    use RefreshDatabase;

    private AttendanceRecordPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new AttendanceRecordPolicy;
    }

    public function test_本人は自分の勤怠記録を閲覧できる(): void
    {
        $user = User::factory()->create();
        $record = AttendanceRecord::factory()->for($user)->create();

        $this->assertTrue($this->policy->view($user, $record));
    }

    public function test_管理者は他人の勤怠記録を閲覧できる(): void
    {
        $admin = User::factory()->create(['admin_status' => true]);
        $record = AttendanceRecord::factory()->create();

        $this->assertTrue($this->policy->view($admin, $record));
    }

    public function test_本人でも管理者でもない場合は勤怠記録を閲覧できない(): void
    {
        $user = User::factory()->create();
        $record = AttendanceRecord::factory()->create();

        $this->assertFalse($this->policy->view($user, $record));
    }

    public function test_本人は自分の勤怠記録を修正できる(): void
    {
        $user = User::factory()->create();
        $record = AttendanceRecord::factory()->for($user)->create();

        $this->assertTrue($this->policy->update($user, $record));
    }

    public function test_管理者は他人の勤怠記録を修正できる(): void
    {
        $admin = User::factory()->create(['admin_status' => true]);
        $record = AttendanceRecord::factory()->create();

        $this->assertTrue($this->policy->update($admin, $record));
    }

    public function test_本人でも管理者でもない場合は勤怠記録を修正できない(): void
    {
        $user = User::factory()->create();
        $record = AttendanceRecord::factory()->create();

        $this->assertFalse($this->policy->update($user, $record));
    }
}
