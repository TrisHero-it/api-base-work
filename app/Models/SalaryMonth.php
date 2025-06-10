<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalaryMonth extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'salary',
        'month',
        'year',
    ];

    protected $casts = [
        'salary' => 'array',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class)->select('id', 'email', 'avatar', 'full_name', 'username', 'role_id', 'quit_work');
    }
}
