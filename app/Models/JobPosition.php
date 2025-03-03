<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobPosition extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'job_position',
        'start_date',
        'end_date',
        'salary',
        'status',
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
