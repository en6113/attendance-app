<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceRecord extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'date',
        'clock_in_time',
        'clock_out_time',
        'comment',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date',
        'clock_in_time' => 'datetime',
        'clock_out_time' => 'datetime',
    ];

    /**
     * この勤怠記録の所有者(多対1)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * この勤怠記録に紐づく休憩記録（1対多）
     */
    public function breaks(): HasMany
    {
        return $this->hasMany(BreakTime::class);
    }

    /**
     * この勤怠記録に紐づく修正申請（1対多）
     */
    public function correctRequests(): HasMany
    {
        return $this->hasMany(AttendanceCorrectRequest::class);
    }

    /**
     * 出勤時刻を「H:i」形式の文字列に整形したアクセサ。未出勤の場合は空文字を返す。
     *
     * @return Attribute 出勤時刻の文字列
     */
    protected function clockIn(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->clock_in_time?->format('H:i') ?? '',
        );
    }

    /**
     * 退勤時刻を「H:i」形式の文字列に整形したアクセサ。未退勤の場合は空文字を返す。
     *
     * @return Attribute 退勤時刻の文字列
     */
    protected function clockOut(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->clock_out_time?->format('H:i') ?? '',
        );
    }

    /**
     * 休憩時間の合計を「H:i:s」形式の文字列に整形したアクセサ。休憩がない場合は空文字を返す。
     *
     * @return Attribute 休憩時間合計の文字列
     */
    protected function totalBreakTime(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->totalBreakSeconds() > 0 ? gmdate('H:i:s', $this->totalBreakSeconds()) : '',
        );
    }

    /**
     * 出勤〜退勤の実働時間（休憩時間を除く）を「H:i:s」形式の文字列に整形したアクセサ。
     * 出勤・退勤のいずれかが未登録の場合は空文字を返す。
     *
     * @return Attribute 実働時間の文字列
     */
    protected function totalTime(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                if (! $this->clock_in_time || ! $this->clock_out_time) {
                    return '';
                }

                $workSeconds = $this->clock_in_time->diffInSeconds($this->clock_out_time) - $this->totalBreakSeconds();

                return gmdate('H:i:s', $workSeconds);
            },
        );
    }

    /**
     * 休憩時間の合計を秒数で算出する。開始・終了のいずれかが未登録の休憩は0秒として計算する。
     *
     * @return int 休憩時間の合計秒数
     */
    private function totalBreakSeconds(): int
    {
        return $this->breaks->sum(
            fn (BreakTime $break) => $break->break_start_time && $break->break_end_time
            ? $break->break_start_time->diffInSeconds($break->break_end_time)
            : 0
        );
    }

    /**
     * 出勤〜退勤の実働時間（休憩時間を除く）を分単位で算出する。
     * 出勤・退勤のいずれかが未登録の場合は0を返す。
     *
     * @return int 実働時間（分）
     */
    public function workMinutes(): int
    {
        if (! $this->clock_in_time || ! $this->clock_out_time) {
            return 0;
        }

        $workSeconds = $this->clock_in_time->diffInSeconds($this->clock_out_time) - $this->totalBreakSeconds();

        return intdiv($workSeconds, 60);
    }
}
