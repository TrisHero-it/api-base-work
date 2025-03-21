<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoryResource extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    public function resources()
    {
        return $this->hasMany(Resource::class);
    }
}
