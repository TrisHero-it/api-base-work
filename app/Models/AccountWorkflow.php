<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountWorkflow extends Model
{
    use HasFactory;

    protected $fillable =  [
        'account_id',
        'workflow_id',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
