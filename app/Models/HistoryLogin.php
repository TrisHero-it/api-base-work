<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistoryLogin extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'user_agent',
        'method',
        'ip_address'
    ];
}
