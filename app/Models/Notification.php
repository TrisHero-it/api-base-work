<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'message',
        'link',
        'seen',
        'account_id',
        'manager_id',
        'is_notice',
        'thumbnail',
        'is_hidden'
    ];

    protected $casts = [
        'seen_by' => 'array',
    ];

    public function manager()
    {

        return $this->belongsTo(Account::class)->select('id', 'full_name', 'avatar');
    }

    public function account()
    {

        return $this->belongsTo(Account::class)->select('id', 'full_name', 'avatar');
    }
}
