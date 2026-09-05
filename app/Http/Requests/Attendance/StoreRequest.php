<?php

namespace App\Http\Requests\Attendance;

use Closure;
use Illuminate\Foundation\Http\FormRequest;

/**
 * 勤怠詳細画面（一般ユーザー）からの修正申請を検証するrequest。
 */
class StoreRequest extends FormRequest
{
    /**
     * 時・分の区切りが「:」の時刻文字列（例: 9:00, 09:00, 23:59）を許可する正規表現。
     */
    private const TIME_PATTERN = '/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/';

    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('id'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'new_clock_in' => ['bail', 'required', 'regex:'.self::TIME_PATTERN],
            'new_clock_out' => [
                'bail',
                'required',
                'regex:'.self::TIME_PATTERN,
                function (string $attribute, mixed $value, Closure $fail): void {
                    $clockIn = $this->input('new_clock_in');

                    if ($clockIn && $this->toMinutes($value) <= $this->toMinutes($clockIn)) {
                        $fail('出勤時間もしくは退勤時間が不適切な値です');
                    }
                },
            ],
            'new_break_in' => ['array'],
            'new_break_in.*' => [
                'bail',
                'nullable',
                'required_with:new_break_out.*',
                'regex:'.self::TIME_PATTERN,
                function (string $attribute, mixed $value, Closure $fail): void {
                    $clockIn = $this->input('new_clock_in');
                    $clockOut = $this->input('new_clock_out');

                    if (($clockIn && $this->toMinutes($value) < $this->toMinutes($clockIn))
                        || ($clockOut && $this->toMinutes($value) > $this->toMinutes($clockOut))) {
                        $fail('休憩時間が不適切な値です');
                    }
                },
            ],
            'new_break_out' => ['array'],
            'new_break_out.*' => [
                'bail',
                'nullable',
                'required_with:new_break_in.*',
                'regex:'.self::TIME_PATTERN,
                function (string $attribute, mixed $value, Closure $fail): void {
                    $clockOut = $this->input('new_clock_out');

                    if ($clockOut && $this->toMinutes($value) > $this->toMinutes($clockOut)) {
                        $fail('休憩時間もしくは退勤時間が不適切な値です');
                    }
                },
            ],
            'comment' => ['required'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'comment.required' => '備考を記入してください',
            'new_clock_in.regex' => '出勤時間もしくは退勤時間が不適切な値です',
            'new_clock_out.regex' => '出勤時間もしくは退勤時間が不適切な値です',
            'new_break_in.*.regex' => '休憩時間が不適切な値です',
            'new_break_out.*.regex' => '休憩時間もしくは退勤時間が不適切な値です',
        ];
    }

    /**
     * "H:i"形式の時刻文字列を、0時からの経過分数に変換する。
     */
    private function toMinutes(string $time): int
    {
        [$hour, $minute] = explode(':', $time);

        return ((int) $hour) * 60 + (int) $minute;
    }
}
