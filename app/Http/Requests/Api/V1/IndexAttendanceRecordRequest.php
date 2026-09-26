<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 勤怠一覧取得(公開API)のリクエストを検証するrequest。
 */
class IndexAttendanceRecordRequest extends FormRequest
{
    /**
     * このリクエストの実行が許可されているかを判定する。一覧取得は認証不要のため常にtrueを返す。
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * バリデーションルール。
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['nullable', 'integer'],
            'date' => ['nullable', 'date_format:Y-m-d'],
            'month' => ['nullable', 'date_format:Y-m'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
