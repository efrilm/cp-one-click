<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'status',
        'clock_in',
        'clock_out',
        'late',
        'early_leaving',
        'overtime',
        'total_rest',
        'early_minutes',
        'early_hours',
        'late_minutes',
        'late_hours',
        'clock_minutes',
        'clock_hours',
    ];

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function locations()
    {
        return $this->hasMany(AttendanceLocation::class, 'attendance_id', 'id');
    }
}
