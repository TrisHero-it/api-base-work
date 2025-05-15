<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HistoryMoveTask;
use App\Models\Stage;
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
            ->get();

        $subQuery = HistoryMoveTask::query()
            ->selectRaw('MAX(id) as id')
            ->where(function ($query) use ($stages) {
                $query->whereIn('old_stage', $stages->pluck('id'))
                    ->orWhereIn('new_stage', $stages->pluck('id'));
            })
            ->whereYear('started_at', $year)
            ->whereMonth('started_at', $month)
            ->groupBy('task_id', 'old_stage', 'new_stage');

        $historyMoveTasks = HistoryMoveTask::query()
            ->whereIn('id', $subQuery)
            ->get();

        $totalTasks = $historyMoveTasks->count();
        

        return response()->json([
            'total_tasks' => $totalTasks,
        ]);
    }
}
