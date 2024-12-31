<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\HistoryMoveTask;
use App\Models\Stage;
use App\Models\Task;
use Illuminate\Http\Request;

class HistoryMoveTaskController extends Controller
{
    public function index(Request $request)
    {
        $task = Task::query()->findOrFail($request->task_id);
        if (isset($request->stage_id)) {
            $history = HistoryMoveTask::query()
                ->where('task_id', $task->id)
                ->where('old_stage', $request->stage_id)
                ->where('worker', '!=', null)
                ->latest('id')->first();

            return $history;
        }
        $histories = HistoryMoveTask::query()
            ->where('task_id', $task->id)
            ->orderBy('id', 'desc')
            ->get();
        $arrAccountId = $histories->pluck('account_id');
        $accounts = Account::query()->whereIn('id', $arrAccountId)->get();
        $arrOldStage = $histories->pluck('old_stage');
        $stages1 = Stage::query()->whereIn('id', $arrOldStage)->get();
        $arrNewStage = $histories->pluck('new_stage');
        $stages2 = Stage::query()->whereIn('id', $arrNewStage)->get();
        foreach ($histories as $history) {
            foreach ($accounts as $account) {
                if ($account->id == $history->account_id) {
                    $name = $account->full_name;
                }
            }
            $history['full_name'] = $name;
            foreach ($stages1 as $stage) {
                if ($stage->id == $history->old_stage) {
                    $stageT = $stage;
                    $history['name_old_stage'] = $stageT->name;
                }
            }

            foreach ($stages2 as $stage) {
                if ($stage->id == $history->new_stage) {
                    $stageT = $stage;
                    $history['name_new_stage'] = $stageT->name;
                }
            }
        }



        return response()->json($histories);
    }

    public function timeStage(Request $request, int $idTask) {
        $task = Task::query()->find($idTask);
        $stages = Stage::query()
            ->where('workflow_id', $task->stage->workflow_id)
            ->orderByDesc('index')
            ->get();
        $arrStageId = $stages->pluck('id');
        $arrHistoryMoveTask = HistoryMoveTask::query()
            ->whereIn('old_stage', $arrStageId)
            ->where('task_id', $idTask)
            ->where('worker', '!=', null)
            ->get();
        $arrAccountId = $arrHistoryMoveTask->pluck('worker');
        $accounts = Account::query()->whereIn('id', $arrAccountId)->get();
        $data = [];
        foreach ($stages as $stage) {
            $a = $arrHistoryMoveTask->where('old_stage', $stage->id);
            $a = array_values($a->toArray());
            if (!empty($a)){
                $a = $a[0];
                if ($a['worker'] != null) {
                    $account = $accounts->where('id', $a['worker']);
                    $account = array_values($account->toArray())[0];
                }
            }else {
                $account = null;
            }
            if ($task->account_id != null && $task->stage->id == $stage->id) {
                $account = Account::query()->where('id', $task->account_id)->first();
            }
            $stage['account'] = $account ?? null;
            if ($stage->index != 0 && $stage->index != 1) {
                $histories = $arrHistoryMoveTask->where('old_stage', $stage->id);
                $totalHours = 0;
                $totalMinutes = 0;
                foreach ($histories as $history) {
                    $oldDate = null;
                    $newDate = null;
                    $diff = null;
                    $hours = null;
                    $minutes = null;
                    if ($history->started_at != null) {
                        $oldDate = new \DateTime($history->started_at);
                        $newDate = new \DateTime($history->created_at);
                        $diff = $newDate->diff($oldDate);
                        $hours = $diff->h;
                        $minutes = $diff->i;
                    }
                    $totalHours += $hours;
                    $totalMinutes += $minutes;
                }
                $minutesForHours = floor($totalMinutes/60);
                $minutes = $totalMinutes - $minutesForHours*60;
                $hours = $totalHours + $minutesForHours;
                $stage['hours'] = $hours;
                $stage['minutes'] = $minutes;
                $data[] = $stage;
            }
        }

        return response()->json($data);
    }
}
