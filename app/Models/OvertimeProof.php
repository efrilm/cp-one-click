<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class OvertimeProof extends Model
{
    use HasFactory;

    protected $fillable = [
        'overtime_id',
        'user_id',
        'proof',
        'finish_time',
        'date',
    ];

    public function getProofAttribute($proof)
    {
        return config('app.url') . Storage::url($proof);
    }

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
}
