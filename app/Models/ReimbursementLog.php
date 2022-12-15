<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ReimbursementLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'reimbursement_id',
        'user_id',
        'status',
        'reason',
        'created_at',
    ];

    public function user() {
        return $this->hasOne(user::class, 'id', 'user_id');
    }
}
