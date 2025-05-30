<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FieldTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'value',
        'field_id',
    ];

    public function field()
    {
        return $this->belongsTo(Field::class, 'fields_id');
    }

    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

}
