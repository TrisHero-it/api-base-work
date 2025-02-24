<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Resource extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'category_resource_id',
        'thumbnail',
        'note',
        'text_content',
        'account',
        'password',
        'expired_type',
        'expired_date',
    ];

    public function categoryResource()
    {
        return $this->belongsTo(CategoryResource::class);
    }


}
