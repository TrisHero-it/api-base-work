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
        'old_value',
        'new_value',
        'date_wfh',
    ];

    protected $casts = [
        'old_value' => 'array',
        'new_value' => 'array',
    ];

    public function date_holidays()
    {

        return $this->hasMany(DateHoliday::class, 'propose_id', 'id');
    }

    public function account()
    {

        return $this->belongsTo(Account::class)->select('id', 'username', 'full_name', 'avatar', 'role_id', 'email', 'phone');
    }

    public function approved_by()
    {

        return $this->belongsTo(Account::class, 'approved_by', 'id')->select('id', 'username', 'full_name', 'avatar', 'role_id', 'email', 'phone');
    }

    public function propose_category()
    {

        return $this->belongsTo(ProposeCategory::class, 'propose_category_id', 'id');
    }
}
