<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkflowCategoryStage extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_category_id',
        'name'
    ];

    public function reports() {

        return $this->hasMany(WorkflowCategoryStageReport::class, 'report_stage_id' , 'id');
    }
}
