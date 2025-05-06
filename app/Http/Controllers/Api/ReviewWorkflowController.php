<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Stage;
use App\Models\Task;
use App\Models\Workflow;
use Illuminate\Http\Request;

class ReviewWorkflowController extends Controller
{
    public function index(Request $request)
    {
        $month = isset($request->date) ? explode('-', $request->date)[1] : date('m');
        $year = isset($request->date) ? explode('-', $request->date)[0] : date('Y');
        $workflow = Workflow::where('id', $request->workflow_id)
            ->first();
        $stage = Stage::where('workflow_id', $workflow->id)
            ->get();
        $stageid = $stage->pluck('id');
        $stageSuccess = $stage->where('index', 0)
            ->pluck('id')->toArray();
        $stageFailed = $stage->where('index', 1)
            ->pluck('id')->toArray();
        $stageSuccessAndFailed = array_merge($stageSuccess, $stageFailed);
        $task = Task::whereIn('stage_id', $stageid)
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year);

        $taskProgress = $task->where('expired', null)
            ->whereNotIn('stage_id', $stageSuccessAndFailed)
            ->orWhere('expired', '>', now())
            ->count();
        $taskSuccess = $task->whereIn('stage_id', $stageSuccess)
            ->count();
        $taskFailed = $task->whereIn('stage_id', $stageFailed)
            ->count();
        return response()->json([
            'task_progress' => $taskProgress,
            'task_success' => $taskSuccess, 
            'task_failed' => $taskFailed,
        ]);
    }
}
