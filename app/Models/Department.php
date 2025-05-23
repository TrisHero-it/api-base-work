<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;
    protected $fillable = [
        'name'
    ];

    public function workflowCategories()
    {
        return $this->hasMany(WorkflowCategory::class);
    }

    public function members()
    {
        return $this->hasManyThrough(Account::class, AccountDepartment::class, 'department_id', 'id', 'id', 'account_id');
    }
}
