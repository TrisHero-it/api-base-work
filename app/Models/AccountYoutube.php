<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountYoutube extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'password',
        'name',
        'token_2fa',
        'type',
        'index',
    ];

    protected $hidden = [
        'token_2fa',
    ];

        
}
