<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\HistoryMoveTask;
use App\Models\Stage;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ScheduleWorkController extends Controller
{
        public function index(Request $request)
        {
            // Tuần này
            $endOfThisWeek = Carbon::now()->endOfWeek()->toDateString();
            // Tuần trước
            $startOfLastWeek = Carbon::now()->startOfWeek()->toDateString();
            $startDate = Carbon::parse($startOfLastWeek);
            $endDate = Carbon::parse($endOfThisWeek);
                // Lặp qua từng ngày
                $arr = [];
                for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
                     $a = Task::query()->select('name as name_task','account_id', 'started_at', 'expired', 'stage_id')
                        ->whereDate('expired', $date)
                        ->OrwhereDate('started_at', $date)
                        ->orderBy('expired');

                     if (isset($request->account_id)) {
                         $a->where('account_id', $request->account_id);
                     }

                     $a = $a->get();
                     if (!empty($a)) {
                         foreach ($a as $task) {
                             $task['account_name']= $task->account->full_name;
                             $task['avatar'] = $task->account->avatar;
                             $task['stage_name'] = $task->stage->name;
                             $task->status = array_rand(['in_progress'=>'đang làm', 'failed'=>'Thất bại', 'completed'=>'Thành công']);
                             unset($task->account);
                             unset($task->stage_id);
                             unset($task->stage);
                         }
                     }

                    $b = DB::table('history_move_tasks')->whereDate('started_at', $date)
                        ->select('task_id', 'old_stage','worker')
                        ->orWhereDate('expired_at', $date)
                        ->groupBy('task_id', 'old_stage', 'worker');


                    if (isset($request->account_id)) {
                        $b->where('worker', $request->account_id);
                    }

                    $b = $b->get();

                     foreach ($b as $task) {
                         $c = Task::query()->select('name as name_task','account_id', 'started_at', 'expired')->where('id', $task->task_id)->first();
                         $acc = Account::query()->where('id', $task->worker)->first();
                         $task->name_task= $c->name_task;
                         $task->stage_name = Stage::query()->where('id', $task->old_stage)->first()->name;
                         $task->account_name= $acc->full_name;
                         $task->avatar= $acc->avatar;
                         $task->started_at= HistoryMoveTask::query()->where('task_id', $task->task_id)
                         ->where('old_stage', $task->old_stage)
                         ->where('worker', $task->worker)
                         ->first()->started_at;
                         $task->expired_at = HistoryMoveTask::query()->where('task_id', $task->task_id)
                             ->where('old_stage', $task->old_stage)
                             ->where('worker', $task->worker)
                             ->first()->expired_at;
                         $task->status = array_rand(['in_progress'=>'đang làm', 'failed'=>'Thất bại', 'completed'=>'Thành công']);
                         unset($task->worker);
                         unset($task->old_stage);
                     }
                     $a = $a->toArray();
                     $b = $b->toArray();

                     $arr[$date->format('Y-m-d')] = $a+$b;
            }

            return $arr;
        }
}
