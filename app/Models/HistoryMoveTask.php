<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistoryMoveTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'task_id',
        'old_stage',
        'new_stage',
        'started_at',
        'worker'
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function task(){
        return $this->belongsTo(Task::class);
    }

    public function oldStage(){

        return $this->belongsTo(Stage::class, 'old_stage');
    }

    public function newStage(){
        return $this->belongsTo(Stage::class, 'new_stage');
    }
}
