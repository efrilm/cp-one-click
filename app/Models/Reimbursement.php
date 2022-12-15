<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Reimbursement extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'user_id',
        'name',
        'description',
        'date',
        'proof',
        'status',
        'status_paid',
    ];

    public function getProofAttribute($proof)
    {
        return config('app.url') . Storage::url($proof);
    }

    public function user() {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function items() {
        return $this->hasMany(ReimbursementItem::class, 'reimbursement_id', 'id');
    }

    public function payment() {
        return $this->hasOne(ReimbursementPayment::class, 'reimbursement_id', 'id');
    }

   
}
