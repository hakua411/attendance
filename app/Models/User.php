<?php

namespace App\Models;

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

    /**
     * ユーザーに紐づく勤怠記録を取得する。
     *
     * @return HasMany<Attendance, $this>
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * ユーザーに紐づく修正申請を取得する。
     *
     * @return HasMany<Application, $this>
     */
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    /**
     * 現在の勤怠ステータスを取得する。
     *
     * @return string 勤務外・出勤中・休憩中・退勤済のいずれか
     */
    public function getAttendanceStatusAttribute(): string
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
