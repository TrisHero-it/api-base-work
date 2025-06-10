<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class YoutubeUpLoad extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'hashtags',
        'video_url',
        'title_game',
        'upload_date',
        'status',
        'channel_name',
        'task_id',
    ];
    
    
}
