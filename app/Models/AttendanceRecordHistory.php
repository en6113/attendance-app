<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecordHistory extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'attendance_record_id',
        'attendance_correct_request_id',
        'date',
        'clock_in_time',
        'clock_out_time',
        'comment',
        'breaks',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date',
        'clock_in_time' => 'datetime',
        'clock_out_time' => 'datetime',
        'breaks' => 'array',
    ];

    public function attendanceRecord(): BelongsTo
    {
        return $this->belongsTo(AttendanceRecord::class);
    }

    public function attendanceCorrectRequest(): BelongsTo
    {
        return $this->belongsTo(AttendanceCorrectRequest::class);
    }
}
