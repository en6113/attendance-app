<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceCorrectRequest extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'attendance_record_id',
        'is_direct_edit',
        'old_date',
        'old_clock_in',
        'old_clock_out',
        'old_comment',
        'old_breaks',
        'new_date',
        'new_clock_in',
        'new_clock_out',
        'comment',
        'approved_at',
        'application_date',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'is_direct_edit' => 'boolean',
        'old_date' => 'date',
        'old_clock_in' => 'datetime',
        'old_clock_out' => 'datetime',
        'old_breaks' => 'array',
        'new_date' => 'date',
        'approved_at' => 'datetime',
        'application_date' => 'date',
    ];

    /**
     * この修正申請の対象となる勤怠記録（多対1）
     */
    public function attendanceRecord(): BelongsTo
    {
        return $this->belongsTo(AttendanceRecord::class);
    }

    /**
     * この修正申請に紐づく休憩の修正内容（1対多）。
     */
    public function proposalBreaks(): HasMany
    {
        return $this->hasMany(ProposalBreak::class);
    }

    /**
     * approved_atの有無から承認状況を判定するアクセサ。
     *
     * @return Attribute 「承認済み」または「承認待ち」
     */
    protected function approvalStatus(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->approved_at ? '承認済み' : '承認待ち',
        );
    }

    /**
     * この修正申請を行ったユーザー。勤怠記録経由で2段階の関連をたどって取得する。
     *
     * @return Attribute 申請者のユーザー
     */
    protected function user(): Attribute
    {
        return Attribute::make(
            get: fn (): User => $this->attendanceRecord->user,
        );
    }
}
