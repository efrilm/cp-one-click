<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShiftingLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'shifting_change_id',
        'user_id',
        'status',
        'reason',
    ];
    
    public function shiftingChange() {
        return $this->hasOne(ShiftingChange::class, 'id', 'shifting_change_id');
    }

    public function user() {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
}
