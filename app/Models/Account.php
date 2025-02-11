<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Account extends Authenticatable
{
    use HasFactory, Notifiable;
    protected $fillable = [
        "username",
        "email",
        "password",
        "full_name",
        'position',
        'phone',
        'address',
        'birthday',
        'manager_id',
        'avatar',
        'role_id',
        'day_off',
        'attendance_at_home'
    ];

    public function isAdmin()
    {

        return $this->role_id == 1 || $this->role_id == 2;
    }

    public function isSeniorAdmin()
    {
        return $this->role_id == 2;
    }
}
