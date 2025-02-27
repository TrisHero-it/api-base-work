<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Resource extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'category_resource_id',
        'thumbnail',
        'note',
        'text_content',
        'account',
        'password',
        'expired_date',
        'notification_before_days',
        'notification_before_hours',
    ];

    public function categoryResource()
    {
        return $this->belongsTo(CategoryResource::class);
    }

    public function members()
    {
        return $this->belongsToMany(Account::class, 'account_resources', 'resource_id', 'account_id');
    }

    public function receivers()
    {
        return $this->belongsToMany(Account::class, 'notification_resources', 'resource_id', 'account_id');
    }


}
