<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\Attendance\StoreRequest;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreRequestTest extends TestCase
{
    /**
     * @param  array<string, mixed>  $input
     */
    private function validate(array $input): ValidatorContract
    {
        $request = StoreRequest::create('/attendance/detail/1', 'POST', $input);

        return Validator::make($request->all(), $request->rules(), $request->messages());
    }

    public function test_出勤退勤と休憩の入力が正しければバリデーションを通過する(): void
    {
        $validator = $this->validate([
            'new_clock_in' => '09:00',
            'new_clock_out' => '18:00',
            'new_break_in' => ['12:00'],
            'new_break_out' => ['13:00'],
            'comment' => '電車遅延のため',
        ]);

        $this->assertFalse($validator->fails());
    }

    public function test_出勤時間が1桁の時刻でもバリデーションを通過する(): void
    {
        $validator = $this->validate([
            'new_clock_in' => '9:00',
            'new_clock_out' => '18:00',
            'new_break_in' => [],
            'new_break_out' => [],
            'comment' => '備考',
        ]);

        $this->assertFalse($validator->fails());
    }

    public function test_出勤時間が退勤時間より後だとエラーになる(): void
    {
        $validator = $this->validate([
            'new_clock_in' => '18:00',
            'new_clock_out' => '09:00',
            'new_break_in' => [],
            'new_break_out' => [],
            'comment' => '備考',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertSame('出勤時間もしくは退勤時間が不適切な値です', $validator->errors()->first('new_clock_out'));
    }

    public function test_休憩開始時間が出勤時間より前だとエラーになる(): void
    {
        $validator = $this->validate([
            'new_clock_in' => '09:00',
            'new_clock_out' => '18:00',
            'new_break_in' => ['08:00'],
            'new_break_out' => ['08:30'],
            'comment' => '備考',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertSame('休憩時間が不適切な値です', $validator->errors()->first('new_break_in.0'));
    }

    public function test_休憩終了時間が退勤時間より後だとエラーになる(): void
    {
        $validator = $this->validate([
            'new_clock_in' => '09:00',
            'new_clock_out' => '18:00',
            'new_break_in' => ['17:00'],
            'new_break_out' => ['19:00'],
            'comment' => '備考',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertSame('休憩時間もしくは退勤時間が不適切な値です', $validator->errors()->first('new_break_out.0'));
    }

    public function test_休憩の開始か終了どちらかだけ入力するとエラーになる(): void
    {
        $validator = $this->validate([
            'new_clock_in' => '09:00',
            'new_clock_out' => '18:00',
            'new_break_in' => ['12:00'],
            'new_break_out' => [''],
            'comment' => '備考',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('new_break_out.0'));
    }

    public function test_休憩欄を両方空欄にすればバリデーションを通過する(): void
    {
        $validator = $this->validate([
            'new_clock_in' => '09:00',
            'new_clock_out' => '18:00',
            'new_break_in' => [''],
            'new_break_out' => [''],
            'comment' => '備考',
        ]);

        $this->assertFalse($validator->fails());
    }

    public function test_備考が未入力だとエラーになる(): void
    {
        $validator = $this->validate([
            'new_clock_in' => '09:00',
            'new_clock_out' => '18:00',
            'new_break_in' => [],
            'new_break_out' => [],
            'comment' => '',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertSame('備考を記入してください', $validator->errors()->first('comment'));
    }
}
