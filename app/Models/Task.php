<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        "code",
        "description",
        "account_id",
        "stage_id",
        'failed_at',
        'reason'
    ];

    public function stage()
    {
        return $this->belongsTo(Stage::class);
    }
}
