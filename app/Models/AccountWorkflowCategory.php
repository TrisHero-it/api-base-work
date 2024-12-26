<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountWorkflowCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'workflow_category_id',
        'department_id'
    ];

    public function account() {

        return $this->belongsTo(Account::class);
    }

    public function department() {

        return $this->belongsTo(Department::class);
    }
}
