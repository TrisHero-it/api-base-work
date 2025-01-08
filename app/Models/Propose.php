<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Propose extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'account_id',
        'description',
        'status',
        'propose_category_id',
        'approved_by',
        'reason',
        'start_time',
        'end_time',
    ];

    public function account()
    {

        return $this->belongsTo(Account::class);
    }

    public function propose_category()
    {

        return $this->belongsTo(ProposeCategory::class);
    }
}
