<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveLog extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'leave_id',
        'user_id',
        'status',
        'reason',
        'created_at',
    ];

    public function user() {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
}
