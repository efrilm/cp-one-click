<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use PhpParser\Node\Expr\FuncCall;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'head_id',
        'name',
        'address',
        'longitude',
        'latitude',
        'start_date',
        'end_date',
        'status',
    ];

    public function head() {
        return $this->hasOne(User::class, 'id', 'head_id');
    }

    public function detail() {
        return $this->hasMany(ProjectUser::class, 'project_id', 'id');
    }
}
