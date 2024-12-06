<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkflowCategoryStageReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_stage_id'
        ,'name',
        'type'
    ];

    public function fields()
    {

        return $this->hasMany(Field::class, 'report_rule_id' , 'id');
    }
}
