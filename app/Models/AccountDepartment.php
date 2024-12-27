<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountDepartment extends Model
{
    use HasFactory;
    protected $fillable = [
        'account_id',
        'department_id',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
