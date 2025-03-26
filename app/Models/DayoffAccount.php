<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DayoffAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'dayoff_count',
        'dayoff_long_time_worker'
    ];
}
