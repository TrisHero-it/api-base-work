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
        'expired_at',
        'worker',
        'status'
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function worker()
    {
        return $this->belongsTo(Account::class, 'worker', 'id')
            ->select('id', 'email', 'full_name', 'avatar', 'username');
    }

    public function doer()
    {
        return $this->belongsTo(Account::class, 'worker', 'id')
            ->select('id', 'email', 'full_name', 'avatar', 'username');
    }

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function oldStage()
    {

        return $this->belongsTo(Stage::class, 'old_stage');
    }

    public function newStage()
    {
        return $this->belongsTo(Stage::class, 'new_stage');
    }
}
