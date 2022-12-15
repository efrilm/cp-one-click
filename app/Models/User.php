<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'name',
        'email',
        'password',
        'type_id',
        'avatar',
        'dob',
        'gender',
        'phone',
        'address',
        'position_id',
        'company_doj',
        'model_face',
        'shifting_id',
        'expired_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function position() {
        return $this->hasOne(Position::class, 'id', 'position_id');
    }

    public function type() {
        return $this->hasOne(Type::class, 'id', 'type_id');
    }

    public function shifting() {
        return $this->hasOne(Shifting::class, 'id', 'shifting_id');
    }


    public function attendances() {
        return $this->hasMany(Attendance::class, 'user_id', 'id');
    }

    public function overtimes() {
        return $this->hasMany(Overtime::class, 'receiver_id', 'id');
    }

}
