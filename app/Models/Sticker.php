<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sticker extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'workflow_id',
        'code_color'
    ];

    public function tasks()
    {

        return $this->belongsToMany(Task::class, 'task_tag', 'sticker_id', 'task_id');
    }
}
