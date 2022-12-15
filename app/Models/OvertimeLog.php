<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OvertimeLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'overtime_id',
        'user_id',
        'status',
        'reason',
    ];

    public function overtime() {
        return $this->hasOne(Overtime::class, 'id', 'overtime_id');
    }

    public function user() {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

}
