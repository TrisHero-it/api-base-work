<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Field extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'workflow_id',
        'stage_id',
        'require',
        'type',
        'options',
        'model',
        'report_rule_id'
    ];

    protected $casts = [
        'options' => 'array'
    ];

    public function stage()
    {
        return $this->belongsTo(Stage::class);
    }
}
