<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReimbursementItem extends Model
{
    use HasFactory;
    protected $fillable = [
        'reimbursement_id',
        'item_name',
        'request_amount',
        'description',
    ];
}
