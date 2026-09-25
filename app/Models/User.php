<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
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

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function applications()
    {
        return $this->hasMany(Application::class);
    }

    public function getAttendanceStatusAttribute()
    {
        $attendance = $this->attendances()
            ->whereDate('date', today())
            ->latest()
            ->first();

        if (! $attendance) {
            return '勤務外';
        }

        if ($attendance->clock_out) {
            return '退勤済';
        }

        $break = $attendance->breaks()
            ->whereNull('break_out')
            ->latest()
            ->first();

        if ($break) {
            return '休憩中';
        }

        return '出勤中';
    }
}
