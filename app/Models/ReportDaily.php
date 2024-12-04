<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportDaily extends Model
{
    use HasFactory;

    protected $fillable = [
        'kpi_id',
        'description',
        'rate',
        'feedback'
    ];

    public function kpi()
    {
        return $this->belongsTo(Kpi::class);
    }
}
