<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkingTimeTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'start_time',
        'end_time',
        'task_id',
        'account_id',
        'stage_id'
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function stage()
    {
        return $this->belongsTo(Stage::class);
    }
}
