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
        'status',
        'total_time'
    ];

    const STATUS =  [
        'Hoàn thành qúa hạn',
        'Hoàn thành đúng thời hạn'
    ];

    public function getStatus2Attribute()
    {
        return self::STATUS[$this->status] ;
    }

    public function stage()
    {
        return $this->belongsTo(Stage::class);
    }

    public function task() {
        return $this->belongsTo(Task::class);
    }
}
