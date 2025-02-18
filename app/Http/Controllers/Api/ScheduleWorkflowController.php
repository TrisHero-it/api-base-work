<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Stage;
use App\Models\Task;
use App\Models\Workflow;
use Illuminate\Http\Request;

class ScheduleWorkflowController extends Controller
{
    public function index(Request $request)
    {
        $workflows = Workflow::all();
        foreach ($workflows as $workflow) {
            $successStage = Stage::where('workflow_id', $workflow->id)
                ->where('index', 1)
                ->first();
            $idStage = Stage::where('workflow_id', $workflow->id)->get()->pluck('id');
            $failedStage = Stage::where('workflow_id', $workflow->id)
                ->where('index', 0)
                ->first();
            $countTaskCompleted = Task::where('stage_id', $successStage->id)
                ->count();
            $countTaskFailed = Task::where('stage_id', $failedStage->id)
                ->count();
            $countTaskNotExpired = Task::where('expired', null)->whereIn('stage_id', $idStage)
                ->get()
                ->count();
            $workflow->count_task_completed = $countTaskCompleted;
            $workflow->count_task_not_expired = $countTaskNotExpired;
            $workflow->count_task_failed = $countTaskFailed;
        }

        return response()->json($workflows);
    }
}
