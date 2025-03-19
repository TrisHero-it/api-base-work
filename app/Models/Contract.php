<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'files',
        'category__contract_id',
        'creator_by',
        'note',
        'status',
        'active',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'files' => 'array',
    ];

    public function category()
    {
        return $this->belongsTo(Category_Contract::class, 'category__contract_id', 'id');
    }
}
