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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function breaks(): HasMany
    {
        return $this->hasMany(BreakTime::class);
    }

    public function correctRequests(): HasMany
    {
        return $this->hasMany(AttendanceCorrectRequest::class);
    }

    protected function clockIn(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->clock_in_time?->format('H:i') ?? '',
        );
    }

    protected function clockOut(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->clock_out_time?->format('H:i') ?? '',
        );
    }

    protected function totalBreakTime(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->totalBreakSeconds() > 0 ? gmdate('H:i:s', $this->totalBreakSeconds()) : '',
        );
    }

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

    private function totalBreakSeconds(): int
    {
        return $this->breaks->sum(
            fn (BreakTime $break) => $break->break_start_time && $break->break_end_time
            ? $break->break_start_time->diffInSeconds($break->break_end_time)
            : 0
        );
    }

    public function workMinutes(): int
    {
        if (! $this->clock_in_time || ! $this->clock_out_time) {
            return 0;
        }

        $workSeconds = $this->clock_in_time->diffInSeconds($this->clock_out_time) - $this->totalBreakSeconds();

        return intdiv($workSeconds, 60);
    }
}
