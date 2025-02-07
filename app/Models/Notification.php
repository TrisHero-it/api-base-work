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
        'new'
    ];

    public function manager()
    {

        return $this->belongsTo(Account::class);
    }

    public function account()
    {

        return $this->belongsTo(Account::class);
    }
}
