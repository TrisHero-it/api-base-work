<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'full_name', 
        'position', 
        'number_phone',
        'email',
        'birthday',
        'manager_id',
        'role_id',
        'avatar',
        'address'
    ] ;
}
