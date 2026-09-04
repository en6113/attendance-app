<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\Attendance\AttendanceActionRequest;
use App\Models\AttendanceRecord;
use App\Models\BreakTime;
use App\Models\User;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class AttendanceActionRequestTest extends TestCase
{
    use RefreshDatabase;

    private function validate(User $user, string $action): ValidatorContract
    {
        $request = AttendanceActionRequest::create('/attendance', 'POST', ['action' => $action]);
        $request->setUserResolver(fn () => $user);

        return Validator::make($request->all(), $request->rules());
    }

    public function test_勤務外の状態でclock_out以外のactionを送るとエラーになる(): void
    {
        $user = User::factory()->create();

        $validator = $this->validate($user, 'clock_out');

        $this->assertTrue($validator->fails());
        $this->assertSame('現在のステータスでは、その操作はできません。', $validator->errors()->first('action'));
    }

    public function test_出勤中の状態でclock_inを送るとエラーになる(): void
    {
        $user = User::factory()->create();
        AttendanceRecord::factory()->for($user)->create();

        $validator = $this->validate($user, 'clock_in');

        $this->assertTrue($validator->fails());
        $this->assertSame('現在のステータスでは、その操作はできません。', $validator->errors()->first('action'));
    }

    public function test_休憩中の状態でbreak_inを送るとエラーになる(): void
    {
        $user = User::factory()->create();
        $record = AttendanceRecord::factory()->for($user)->create();
        BreakTime::factory()->for($record)->create();

        $validator = $this->validate($user, 'break_in');

        $this->assertTrue($validator->fails());
        $this->assertSame('現在のステータスでは、その操作はできません。', $validator->errors()->first('action'));
    }

    public function test_退勤済の状態でclock_inを送るとエラーになる(): void
    {
        $user = User::factory()->create();
        AttendanceRecord::factory()->for($user)->create(['clock_out_time' => now()]);

        $validator = $this->validate($user, 'clock_in');

        $this->assertTrue($validator->fails());
        $this->assertSame('現在のステータスでは、その操作はできません。', $validator->errors()->first('action'));
    }
}
