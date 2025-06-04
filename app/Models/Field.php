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
        'require',
        'type',
        'options',
        'description',
    ];

    protected $casts = [
        'options' => 'array'
    ];

    public function stage()
    {
        return $this->belongsTo(Stage::class);
    }
}
