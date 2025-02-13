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
        if (isset($request->end)) {
            $startDate = Carbon::parse($request->start);
            $endDate = Carbon::parse($request->end);
        } else {
            $endDate = Carbon::now()->endOfWeek();
            $startDate = Carbon::now()->startOfWeek();
        }
        // Lặp qua từng ngày
        $arr = [];
        for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
            $a = Task::query()->select('id as task_id', 'name as name_task', 'account_id', 'started_at', 'expired as expired_at', 'stage_id', 'completed_at')
                ->with(['stage', 'account'])
                ->where('account_id', '!=', null)
                ->whereDate('started_at', '<=', $date)
                ->where(function ($query) use ($date) {
                    $query->where(function ($subQuery) use ($date) {
                        $subQuery->whereDate('completed_at', '>=', $date);
                    })
                        ->orWhere(function ($subQuery) use ($date) {
                            $subQuery->whereNull('completed_at')
                                ->where(function ($subSubQuery) use ($date) {
                                    $subSubQuery->whereDate('expired', '>=', $date);
                                    // Nếu $date lớn hơn ngày hiện tại, không lấy task có expired là NULL
                                    if ($date > now()->toDateString()) {
                                        $subSubQuery->whereNotNull('expired');
                                    } else {
                                        $subSubQuery->orWhereNull('expired');
                                    }
                                });
                        });
                })
                ->orderBy('expired_at')
                ->get();
            if (!empty($a)) {
                foreach ($a as $task) {
                    $task['hours_work'] = $this->getHoursWork($task, $date);
                    if ($task->stage_id != null) {
                        $task['stage_name'] = $task->stage->name;
                    }
                    if ($task->expired_at === null) {
                        if ($task->completed_at === null) {
                            $d = 'in_progress';
                        } else {
                            if (Carbon::parse($task->completed_at)->isSameDay($date)) {
                                $d = 'completed';
                            } else {
                                $d = 'in_progress';
                            }
                        }
                    } else {
                        if (carbon::parse($task->expired_at)->greaterThan(Carbon::now())) {
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
            $b = DB::table('history_move_tasks')
                ->select('task_id', 'old_stage', 'worker')
                ->where('worker', '!=', null);
            $b->whereDate('started_at', '<=', $date)
                ->whereDate('created_at', '>=', $date)
                ->where(function ($query) use ($date) {
                    $query->whereDate('expired_at', '>=', $date)
                        ->orWhereNull('expired_at');
                })
                ->groupBy('task_id', 'old_stage', 'worker');
            $b = $b->get();
            foreach ($b as $task) {
                $c = Task::query()->select('id', 'name as name_task', 'account_id', 'started_at', 'expired as expired_at')
                    ->where('id', $task->task_id)
                    ->first();
                $his = HistoryMoveTask::query()->where('task_id', $task->task_id)
                    ->where('old_stage', $task->old_stage)
                    ->where('worker', $task->worker)
                    ->orderBy('id', 'desc')
                    ->first();
                $hoursWork = $this->getHoursWork($his, $date);
                $acc = Account::query()->where('id', $task->worker)->first();
                $task->name_task = $c->name_task;
                $task->task_id = $c->id;
                $task->stage_name = Stage::query()->where('id', $task->old_stage)->first()->name;
                $task->account_id = $acc->id;
                $task->avatar = $acc->avatar;
                $task->started_at = $his->started_at;
                $task->expired_at = $his->expired_at;
                if (($his->started_at < $his->expired_at) || ($his->worker !== null && $his->expired_at === null)) {
                    if (Carbon::parse(time: $his->created_at)->format('Y-m-d') == $date->format('Y-m-d')) {
                        $d = 'completed';
                    } else {
                        $d = 'in_progress';
                    }
                } else {
                    $d = 'failed';
                }
                $task->hours_work = $hoursWork;
                $task->status = $d;
                unset($task->worker);
                unset($task->old_stage);
            }
            $a = $a->toArray();
            $b = $b->toArray();
            $arr[$date->format('Y-m-d')] = array_merge($a, $b);
        }

        return $arr;
    }

    public function getHoursWork($task, $date)
    {

        $hoursWork = 0;
        if (Carbon::parse($task->started_at)->format('Y-m-d') == $date->format('Y-m-d')) {
            $start = Carbon::parse($task->started_at);
        } else {
            $start = Carbon::parse($date->format("Y-m-d") . " 08:30:00");
        }
        if ($start->format('Y-m-d') == now()->format('Y-m-d')) {
            $end = now();
        } else {
            $end = Carbon::parse($start)->setTime(17, 30);
        }
        $innerStart1 = Carbon::parse($start->format("Y-m-d") . " 08:30:00");
        $innerEnd1 = Carbon::parse($start->format("Y-m-d") . " 12:00:00");
        $innerStart2 = Carbon::parse($start->format("Y-m-d") . " 13:30:00");
        $innerEnd2 = Carbon::parse($start->format("Y-m-d") . " 17:30:00");
        if ($innerStart1->greaterThanOrEqualTo($start) && $innerEnd1->lessThanOrEqualTo($end)) {
            $hoursWork = $hoursWork + number_format(3.5, 3);
        } else {
            $validStart = max($innerStart1, $start);
            $validEnd = min($innerEnd1, $end);
            if ($validStart->lessThan($validEnd)) {
                $validHours = $validStart->floatDiffInHours($validEnd, true);
                $hoursWork += number_format($validHours, 3);
            }
        }
        if ($innerStart2->greaterThanOrEqualTo($start) && $innerEnd2->lessThanOrEqualTo($end)) {
            $hoursWork = $hoursWork + number_format(4, 3);
        } else {
            $validStart = max($innerStart2, $start);
            $validEnd = min($innerEnd2, $end);
            if ($validStart->lessThan($validEnd)) {
                $validHours = $validStart->floatDiffInHours($validEnd, true);
                $hoursWork += number_format($validHours, 3);
            }
        }

        return $hoursWork;
    }
}
