<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistoryAsset extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_id',
        'status',
        'account_id'
    ];

    public function account()
    {
        return $this->belongsTo(Account::class)->select('id', 'full_name', 'email', 'phone', 'address');
    }
}
