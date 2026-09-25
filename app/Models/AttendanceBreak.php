<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceBreak extends Model
{
    use HasFactory;

    protected $table = 'breaks';

    protected $fillable = [
        'attendance_record_id',
        'break_in',
        'break_out',
    ];

    protected $casts = [
        'break_in' => 'datetime',
        'break_out' => 'datetime',
    ];

    public function attendance()
    {
        return $this->belongsTo(Attendance::class, 'attendance_record_id');
    }
}
