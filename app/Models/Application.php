<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'attendance_record_id',
        'new_clock_in',
        'new_clock_out',
        'comment',
        'approval_status',
        'application_date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function attendance()
    {
        return $this->belongsTo(Attendance::class, 'attendance_record_id');
    }

    public function applicationBreaks()
    {
        return $this->hasMany(ApplicationBreak::class);
    }
}
