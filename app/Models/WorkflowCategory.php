<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkflowCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    public function workflows()
    {
        return $this->hasMany(Workflow::class);
    }
}
