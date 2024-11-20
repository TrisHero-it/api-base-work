<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kpi extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'stage_id',
        'task_id',
        'status'
    ];

    const STATUS =  [
        'Hoàn thành qúa hạn',
        'Hoàn thành đúng thời hạn'
    ];
}
