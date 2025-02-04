<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Workflow extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'workflow_category_id',
        'is_close',
        'require_link_youtube'
    ];

    public function stages() {
        return $this->hasMany(Stage::class);
    }

    public function category() {

        return $this->belongsTo(WorkflowCategory::class, 'workflow_category_id', 'id');
    }
}
