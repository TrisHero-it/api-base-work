<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersonDocument extends Model
{
    use HasFactory;
    protected $fillable = [
        'account_id',
        'person_documnet_category_id',
        'license_date',
        'expiration_date',
        'note',
        'files',
        'place_of_issue',
    ];

    protected $casts = [
        'files' => 'array',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
