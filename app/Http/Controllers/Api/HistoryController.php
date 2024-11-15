<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AccountProfile;
use App\Models\HistoryMoveTask;
use App\Models\Stage;
use App\Models\Task;
use Illuminate\Http\Request;
use DateTime;


class HistoryController extends Controller
{
    public function index(Request $request)
    {
        $histories = HistoryMoveTask::query()->where('task_id', $request->task_id)->orderBy('id', 'desc')->get();
        foreach ($histories as $history) {
            $name = AccountProfile::query()->where('id', $history->account_id)->first();
            $name = $name->full_name;
            $history['full_name'] = $name;
            $stage = Stage::query()->find($history->old_stage);
            $history['name_old_stage'] = $stage->name;
            $stage = Stage::query()->find($history->new_stage);
            $history['name_new_stage'] = $stage->name;
        }

        return response()->json($histories);
    }

    public function timeStage(Request $request, int $idTask) {
        $task = Task::query()->find($idTask);
        $stages = Stage::query()->where('workflow_id', $task->stage->workflow_id)->orderByDesc('index')->get();
        $data = [];
        foreach ($stages as $stage) {
            $account = AccountProfile::query()->where('email', $task->account_id)->first();
            $stage['account'] = $account ?? null;
           if ($stage->index != 0 && $stage->index != 1) {
               $histories = HistoryMoveTask::query()->where('task_id', $idTask)->where('old_stage', $stage->id)->orderBy('id', 'desc')->get();
               $totalHours = 0;
               $totalMinutes = 0;
               foreach ($histories as $history) {
                   $oldDate = null;
                   $newDate = null;
                   $diff = null;
                   $hours = null;
                   $minutes = null;
                   if ($history->started_at != null) {
                       $oldDate = new DateTime($history->started_at);
                       $newDate = new DateTime($history->created_at);
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
