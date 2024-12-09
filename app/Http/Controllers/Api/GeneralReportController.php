<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Field;
use App\Models\FieldTask;
use App\Models\WorkflowCategoryStage;
use App\Models\WorkflowCategoryStageReport;
use Illuminate\Http\Request;

class GeneralReportController extends Controller
{
    public function index(Request $request)
    {
        $reports = FieldTask::query()->get();
        $a = [];
        foreach ($reports as $report) {
            if ($report->task->stage->workflow->category->id == $request->category_id){

                $a[] =[
                    'Người thực thi' => $report->account->full_name,
                    'Quy trình' => $report->task->stage->workflow->name,
                    'Giai đoạn' => $report->task->stage->name,
                    $report->field->name => $report->value,
                ];
            }
        }

        return response()->json($a);
    }
}
