<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stage extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'workflow_id',
        'description',
        'index',
        'expired_after_hours'
    ];

    public function isSuccessStage()
    {
        return $this->index == 1;
    }

    public function isFailStage()
    {
        return $this->index == 0;
    }


    public function tasks() {
        return $this->hasMany(Task::class);
    }

    public function workflow() {
        return $this->belongsTo(Workflow::class);
    }
}
