<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DayoffAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'total_holiday_with_salary',
        'seniority_holiday',
        'effective_date'
    ];
}
