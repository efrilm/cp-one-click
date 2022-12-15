<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MutationEmployee extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'project_from',
        'project_to',
        'reason',
        'created_by',
    ];

    public function user() {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
    
    public function projectFrom() {
        return $this->hasOne(Project::class, 'id', 'project_from');
    }
    
    public function projectTo() {
        return $this->hasOne(Project::class, 'id', 'project_to');
    }
    
    public function createdBy() {
        return $this->hasOne(User::class, 'id', 'created_by');
    }
}

