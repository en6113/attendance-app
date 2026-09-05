<?php

namespace App\Http\Requests\Attendance;

use Closure;
use Illuminate\Foundation\Http\FormRequest;

/**
 * 勤怠打刻（出勤・休憩入・休憩戻・退勤）のPOSTリクエストを検証するrequest。
 * 現在のattendance_statusと矛盾するactionが送られた場合、エラーメッセージを返す。
 */
class ActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'action' => [
                'bail',
                'required',
                'in:clock_in,clock_out,break_in,break_out',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $allowedActions = match ($this->user()->attendance_status) {
                        '勤務外' => ['clock_in'],
                        '出勤中' => ['clock_out', 'break_in'],
                        '休憩中' => ['break_out'],
                        '退勤済' => [],
                    };

                    if (! in_array($value, $allowedActions, true)) {
                        $fail('現在のステータスでは、その操作はできません。');
                    }
                },
            ],
        ];
    }
}
