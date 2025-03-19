<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Salary extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_position_id',
        'gross_salary',
        'travel_allowance',
        'eat_allowance',
        'net_salary',
    ];
}
