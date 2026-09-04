<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'admin_status' => 'boolean',
    ];

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    protected function attendanceStatus(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                $openRecord = $this->attendanceRecords()
                    ->whereNull('clock_out_time')
                    ->latest('work_date')
                    ->first();

                if ($openRecord) {
                    $hasOpenBreak = $openRecord->breaks()->whereNull('break_end_time')->exists();

                    return $hasOpenBreak ? '休憩中' : '出勤中';
                }

                $hasTodayRecord = $this->attendanceRecords()
                    ->whereDate('work_date', today())
                    ->exists();

                return $hasTodayRecord ? '退勤済' : '勤務外';
            }
        );
    }
}
