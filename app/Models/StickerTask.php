<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StickerTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'sticker_id'
    ];

    public function sticker() {

        return $this->belongsTo(Sticker::class);
    }
}
