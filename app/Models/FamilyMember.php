<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FamilyMember extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $fillable = [
        'name',
        'relationship',
        'is_dependent',
        'phone_number',
        'is_urgent',
        'is_household',
        'account_id',
    ];
}
