<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DateHoliday extends Model
{
    use HasFactory;

    protected $timestapms = false;

    protected $fillable = [
        'propose_id',
        'start_date',
        'end_date',
        'number_of_days'
    ];
}
