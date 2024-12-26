<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        "description",
        "account_id",
        "stage_id",
        'failed_at',
        'reason',
        'expired',
        'status',
        'started_at',
        'kpi',
        'link_youtube',
        'view_count',
        'like_count',
        'comment_count',
        'code_youtube',
        'date_posted',
        'status',
        'completed_at'
    ];

    public function stage()
    {
        return $this->belongsTo(Stage::class);
    }

    public function tags()
    {

        return $this->belongsToMany(Sticker::class, 'sticker_tasks','task_id', 'sticker_id');
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function isNextStage($index) {

        return $this->stage->index > $index;
    }
}
