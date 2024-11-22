<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Account extends Authenticatable
{
    use HasFactory,Notifiable;
    protected $fillable = [
        "username","email","password",
    ];

    public function account_profile(){

        return $this->hasOne(AccountProfile::class, 'email','id');
    }
}
