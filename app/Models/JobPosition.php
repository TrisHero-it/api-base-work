<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobPosition extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'name',
        'status',
        'description',
        'old_postion',
        'reason',
        'files',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function salary()
    {
        return $this->hasOne(Salary::class);
    }
}
