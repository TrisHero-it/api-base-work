<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FieldTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'data_yield_id',
        'task_id',
        'value',
    ];
}
