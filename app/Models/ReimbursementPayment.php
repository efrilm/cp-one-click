<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReimbursementPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'reimbursement_id',
        'payee_id',
        'payer_id',
        'gross_amount',
        'status',
    ];

    public function payee() {
        return $this->hasOne(User::class, 'id', 'payee_id');
    }

    public function payer() {
        return $this->hasOne(User::class, 'id', 'payer_id');
    }
}
