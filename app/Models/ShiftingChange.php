<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShiftingChange extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sent_from',
        'sent_to',
        'shifting_from',
        'shifting_to',
        'reason',
        'status',
        'created_by',
    ];

    public function userFrom() {
        return $this->hasOne(User::class, 'id', 'sent_from');
    }

    public function userTo() {
        return $this->hasOne(User::class, 'id', 'sent_to');
    }

    public function shiftFrom() {
        return $this->hasOne(Shifting::class, 'id', 'shifting_from');
    }

    public function shiftTo() {
        return $this->hasOne(Shifting::class, 'id', 'shifting_to');
    }
}
