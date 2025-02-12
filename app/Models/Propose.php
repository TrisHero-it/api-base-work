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
        'type',
        'old_check_in',
        'old_check_out',
    ];

    public function date_holidays()
    {

        return $this->hasMany(DateHoliday::class, 'propose_id', 'id');
    }

    public function account()
    {

        return $this->belongsTo(Account::class);
    }

    public function propose_category()
    {

        return $this->belongsTo(ProposeCategory::class);
    }
}
