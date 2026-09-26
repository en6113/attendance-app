<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BreakTime extends Model
{
    use HasFactory;

    protected $table = 'breaks';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'attendance_record_id',
        'break_start_time',
        'break_end_time',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'break_start_time' => 'datetime',
        'break_end_time' => 'datetime',
    ];

    /**
     * この休憩記録が属する勤怠記録（多対1）
     */
    public function attendanceRecord(): BelongsTo
    {
        return $this->belongsTo(AttendanceRecord::class);
    }
}
