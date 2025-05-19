<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HistoryMoveTask;
use App\Models\Stage;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReviewWorkflowController extends Controller
{
    public function index(Request $request)
    {
        if ($request->filled('date')) {
            $date = explode('-', $request->date);
            $year = $date[0];
            $month = $date[1];
        } else {
            $year = Carbon::now()->year;
            $month = Carbon::now()->month;
        }

        $stages = Stage::query()
            ->where('workflow_id', $request->workflow_id)
            ->orderBy('index', 'desc')
            ->get();

        $subQuery = HistoryMoveTask::query()
            ->selectRaw('MAX(id) as id')
            ->where(function ($query) use ($stages) {
                $query->whereIn('old_stage', $stages->pluck('id'))
                    ->orWhereIn('new_stage', $stages->pluck('id'));
            })
            ->whereYear('started_at', $year)
            ->whereMonth('started_at', $month)
            ->groupBy('task_id', 'old_stage');

        $historyMoveTasks = HistoryMoveTask::query()
            ->whereIn('id', $subQuery)
            ->get();
        $expiredTasks = 0;
        $successTasks = $historyMoveTasks->where('status', '!=', 'skipped')->count();

        foreach ($historyMoveTasks as $historyMoveTask) {
            if ($historyMoveTask->expired_at != null) {
                if (Carbon::parse($historyMoveTask->created_at)->gt(Carbon::parse($historyMoveTask->expired_at))) {
                    $expiredTasks++;
                }
            }
        }

        foreach ($stages as $stage) {
            $stage->total_tasks = $historyMoveTasks->where('new_stage', $stage->id)
                ->count();
        }

        $totalTasks = $historyMoveTasks->count();

        return response()->json([
            'total_tasks' => $totalTasks,
            'expired_tasks' => $expiredTasks,
            'success_tasks' => $successTasks,
            'progress_tasks' => $totalTasks - $successTasks - $expiredTasks,
            'stages' => $stages,
        ]);
    }
}
