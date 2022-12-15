<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Overtime extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
      'date',  
      'start_time',  
      'end_time',  
      'total_hours',  
      'giver_id',  
      'receiver_id',  
      'status',  
      'created_by',  
    ];

    public function giver() {
        return $this->hasOne(User::class, 'id', 'giver_id');
    }

    public function receiver() {
        return $this->hasOne(User::class, 'id', 'receiver_id');
    }
}
