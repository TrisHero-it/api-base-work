<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class View extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'field_name',
    ];

    protected $casts = [
        'field_name' => 'array',
    ];
}
