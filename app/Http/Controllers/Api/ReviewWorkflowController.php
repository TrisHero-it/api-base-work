<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\HistoryMoveTask;
use App\Models\Stage;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReviewWorkflowController extends Controller
{
    public function index(Request $request, $id)
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
            ->where('workflow_id', $id)
            ->orderBy('index', 'desc')
            ->whereNotIn('index', [1, 0])
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
        $successTasks = $historyMoveTasks->where('status', '!=', 'skipped')
            ->where('expired_at', '!=', null)
            ->count();

        foreach ($historyMoveTasks as $historyMoveTask) {
            if ($historyMoveTask->expired_at != null) {
                if (Carbon::parse($historyMoveTask->created_at)->gt(Carbon::parse($historyMoveTask->expired_at))) {
                    $expiredTasks++;
                }
            }
        }

        foreach ($stages as $stage) {
            $totalTasksOfStage = $historyMoveTasks->where('new_stage', $stage->id)
                ->count();

            $stage->total_tasks = $totalTasksOfStage;

            $successTasksOfStage = $historyMoveTasks->where('status', '!=', 'skipped')
                ->where('expired_at', '!=', null)
                ->where('new_stage', $stage->id)
                ->count();
            $stage->success_tasks = $successTasksOfStage;

            $expiredTasksOfStage = 0;

            foreach ($historyMoveTasks->where('new_stage', $stage->id) as $historyMoveTask) {
                if ($historyMoveTask->expired_at != null) {
                    if (Carbon::parse($historyMoveTask->created_at)->gt(Carbon::parse($historyMoveTask->expired_at))) {
                        $expiredTasksOfStage++;
                    }
                }
            }
            $stage->expired_tasks = $expiredTasksOfStage;
            $stage->progress_tasks = $totalTasksOfStage - $successTasksOfStage - $expiredTasksOfStage;

            $stage->planned_time = "2h";
            $stage->actual_time = "1h";
        }
        $workers = \DB::table('history_move_tasks')
            ->select('worker', \DB::raw('COUNT(*) as total'))
            ->whereNotNull('worker')
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->groupBy('worker')
            ->orderBy('total', 'desc') // tăng dần: ít nhất lên trước
            ->limit(4)
            ->get();

        $workersExpired = \DB::table('history_move_tasks')
            ->select('worker', \DB::raw('COUNT(*) as total'))
            ->whereNotNull('worker')
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->whereColumn('expired_at', '<', 'created_at')
            ->groupBy('worker')
            ->orderBy('total', 'desc') // tăng dần: ít nhất lên trước
            ->limit(4)
            ->get();

        $accounts = Account::select('id', 'full_name', 'avatar', 'email', 'username')
            ->get();
        foreach ($workers as $worker) {
            $worker->full_name = $accounts->where('id', $worker->worker)->first()->full_name;
            $worker->avatar = $accounts->where('id', $worker->worker)->first()->avatar;
            $worker->email = $accounts->where('id', $worker->worker)->first()->email;
            $worker->username = $accounts->where('id', $worker->worker)->first()->username;
        }

        foreach ($workersExpired as $workerExpired) {
            $workerExpired->full_name = $accounts->where('id', $workerExpired->worker)->first()->full_name;
            $workerExpired->avatar = $accounts->where('id', $workerExpired->worker)->first()->avatar;
            $workerExpired->email = $accounts->where('id', $workerExpired->worker)->first()->email;
            $workerExpired->username = $accounts->where('id', $workerExpired->worker)->first()->username;
        }

        $totalTasks = $historyMoveTasks->count();

        return response()->json([
            'total_tasks' => $totalTasks,
            'expired_tasks' => $expiredTasks,
            'success_tasks' => $successTasks,
            'progress_tasks' => $totalTasks - $successTasks - $expiredTasks,
            'stages' => $stages,
            'most_active_worker' => $workers,
            'most_expired_worker' => $workersExpired,
        ]);
    }
}
