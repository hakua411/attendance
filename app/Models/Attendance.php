<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attendance extends Model
{
    use HasFactory;

    protected $table = 'attendance_records';

    protected $fillable = [
        'user_id',
        'date',
        'clock_in',
        'clock_out',
        'comment',
    ];

    protected $casts = [
        'date' => 'date',
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
    ];

    /**
     * この勤怠記録の所有者であるユーザーを取得する。
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * この勤怠記録に紐づく休憩記録を取得する。
     *
     * @return HasMany<AttendanceBreak, $this>
     */
    public function breaks(): HasMany
    {
        return $this->hasMany(AttendanceBreak::class, 'attendance_record_id');
    }

    /**
     * この勤怠記録に紐づく修正申請を取得する。
     *
     * @return HasMany<Application, $this>
     */
    public function applications(): HasMany
    {
        return $this->hasMany(
            Application::class,
            'attendance_record_id'
        );
    }

    /**
     * この勤怠記録の合計休憩時間を分単位で取得する。
     */
    public function getTotalBreakMinutes(): int
    {
        return (int) $this->breaks
            ->filter(
                fn (AttendanceBreak $break) => $break->break_in !== null
                    && $break->break_out !== null
            )
            ->sum(
                fn (AttendanceBreak $break) => (int) $break->break_in->diffInMinutes(
                    $break->break_out
                )
            );
    }

    /**
     * この勤怠記録の実働時間を分単位で取得する。
     */
    public function getTotalWorkMinutes(): int
    {
        if ($this->clock_in === null || $this->clock_out === null) {
            return 0;
        }

        $workMinutes = (int) $this->clock_in->diffInMinutes(
            $this->clock_out
        );

        return max(0, $workMinutes - $this->getTotalBreakMinutes());
    }
}
