<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class YoutubeChannel extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'default_title',
        'default_tags',
        'default_description',
        'playlist',
    ];

    protected $casts = [
        'playlist' => 'array',
    ];

    public function youtubeUploads()
    {
        return $this->hasMany(YoutubeUpload::class);
    }
}
