<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ipWifi extends Model
{
    use HasFactory;

    protected $fillable = ['ip'];
}
