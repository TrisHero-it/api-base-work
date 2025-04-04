<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'type',
        'start_date',
        'end_date',
        'status',
        'files',
        'reason',
        'evaluate'
    ];
}
