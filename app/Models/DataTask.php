<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataTask extends Model
{
    use HasFactory;

    protected $fillable = ['task_id', 'account_id', 'revenue', 'commission_percent', 'net_income'];
}
