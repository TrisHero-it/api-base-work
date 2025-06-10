<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StatusTask extends Model
{
    use HasFactory;
    protected $fillable = [
        'task_id',
        'account_id',
        'stage_id',
        'status',
    ];

}
