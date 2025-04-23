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
        'index',
    ];

    protected $casts = [
        'field_name' => 'array',
    ];

    public function isHigherView($index)
    {
        return $this->index > $index;
    }

    public function isLowerView($index)
    {
        return $this->index < $index;
    }
}
