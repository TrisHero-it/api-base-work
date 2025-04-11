<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HistoryMoveTask;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AffiliateController extends Controller
{
    public function index(Request $request)
    {
        if ($request->filled('month')) {
            $date = explode('-', $request->month);
            $month = $date[1];
            $year = $date[0];
        } else {
            $month = now()->month;
            $year = now()->year;
        }
        $tasksCompleted = Task::query()
            ->whereMonth('completed_at', $month)
            ->whereYear('completed_at', $year)
            ->whereNotNull('code_youtube')
            ->get();

        $idTasks = $tasksCompleted->pluck('id');

        $historyMoveTasks = HistoryMoveTask::query()
            ->with('doer')
            ->selectRaw('task_id, worker, MIN(started_at) as started_at')
            ->whereIn('task_id', $idTasks)
            ->whereNotNull('worker')
            ->groupBy('task_id', 'worker')
            ->get();
        $totalView = 0;
        $totalLike = 0;
        $totalComment = 0;
        $totalClick = 0;
        $totalOrder = 0;
        foreach ($tasksCompleted as $task) {
            $historyMoveTask = $historyMoveTasks->where('task_id', $task->id);
            $arr = [];
            foreach ($historyMoveTask as $history) {
                $arr[] = $history->doer;
            }
            $task->workers = array_values($arr);
            $arrCount = [];
            $arrCount['view_count'] = $task->view_count;
            $arrCount['like_count'] = $task->like_count;
            $arrCount['comment_count'] = $task->comment_count;
            $arrCount['click'] = 0;
            $arrCount['order'] = 0;
            $task->count_youtube = $arrCount;
            $minStartedAt = Carbon::parse($historyMoveTask->min('started_at'));
            $completedAt = Carbon::parse($task->completed_at);
            $task->click = 0;
            $task->order = 0;
            $task->revenue = 0;
            $task->commission_percent = 0;
            $task->net_income = 0;
            $task->completion_time = number_format($minStartedAt->floatDiffInHours($completedAt), 2) . 'h';
            $totalView += $task->view_count;
            $totalLike += $task->like_count;
            $totalComment += $task->comment_count;
            $totalClick += $task->click;
            $totalOrder += $task->order;
        }
        $newData = [];
        $newData['data'] = $tasksCompleted;
        $newData['totalView'] = $totalView;
        $newData['totalLike'] = $totalLike;
        $newData['totalComment'] = $totalComment;
        $newData['totalClick'] = $totalClick;
        $newData['totalOrder'] = $totalOrder;

        return response()->json($newData);
    }
}
