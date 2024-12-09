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
            $a = Task::query()->select('name as name_task', 'account_id', 'started_at', 'expired as expired_at', 'stage_id', 'code')
                ->whereDate('expired', $date)
                ->orWhere(function ($query) use ($date) {
                    $query->whereDate('started_at', $date)
                        ->where('expired', null);
                })
                ->orderBy('expired');
            if (isset($request->account_id)) {
                $a->where('account_id', $request->account_id);
            }
                $a->where('account_id', '!=',null);
            $a = $a->get();
            if (!empty($a)) {
                foreach ($a as $task) {
                    if ($task->account == null) {
                        return $a;
                    }
                    $task['account_name'] = $task->account->full_name;
                    $task['avatar'] = $task->account->avatar;
                    if ($task->stage_id != null) {
                        $task['stage_name'] = $task->stage->name;
                    }
                        if ($task->expired_at === null) {
                            $d = 'in_progress';
                        } else {
                            if (carbon::parse($task->expired)->greaterThan(Carbon::now())) {
                                $d = 'in_progress';
                            } else {
                                $d = 'failed';
                            }
                        }
                    $task->status = $d;
                    unset($task->account);
                    unset($task->stage_id);
                    unset($task->stage);
                }
            }
            $b = DB::table('history_move_tasks')->whereDate('expired_at', $date)
                ->select('task_id', 'old_stage', 'worker')
                ->orWhere(function ($query) use ($date) {
                    $query->whereDate('started_at', $date)
                        ->where('expired_at', null);
                })
                ->groupBy('task_id', 'old_stage', 'worker');
            if (isset($request->account_id)) {
                $b->where('worker', $request->account_id);
            }
            $b = $b->get();
            foreach ($b as $task) {
                $c = Task::query()->select('name as name_task', 'account_id', 'started_at', 'expired as expired_at', 'code')->where('id', $task->task_id)->first();
                $his = HistoryMoveTask::query()->where('task_id', $task->task_id)
                    ->where('old_stage', $task->old_stage)
                    ->where('worker', $task->worker)
                    ->orderBy('id', 'desc')
                    ->first();
                $acc = Account::query()->where('id', $task->worker)->first();
                $task->name_task = $c->name_task;
                $task->code = $c->code;
                $task->stage_name = Stage::query()->where('id', $task->old_stage)->first()->name;
                $task->account_name = $acc->full_name;
                $task->avatar = $acc->avatar;
                $task->started_at = $his->started_at;
                $task->expired_at = $his->expired_at;
                if (($his->started_at < $his->expired_at) || ($his->worker !== null && $his->expired === null)) {
                    $d = 'completed';
                } else {
                    $d = 'failed';
                }
                $task->status = $d;
                unset($task->worker);
                unset($task->old_stage);
            }
            $a = $a->toArray();
            $b = $b->toArray();
            $arr[$date->format('Y-m-d')] = $a + $b;
        }

        return $arr;
    }
}
