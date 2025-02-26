<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\HistoryMoveTask;
use App\Models\Schedule;
use App\Models\Stage;
use App\Models\Task;
use App\Models\Workflow;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ScheduleWorkflowController extends Controller
{
    public function index(Request $request)
    {
        $workflows = Workflow::all();
        if ($request->filled('start')) {
            $start = Carbon::parse($request->start);
            $end = Carbon::parse($request->end);
        } else {
            $start = Carbon::now()->startOfWeek();
            $end = Carbon::now()->endOfWeek();
        }
        $arrSchedule = $this->getScheduleWorkflow($start, $end);
        foreach ($workflows as $workflow) {
            $arrTask = array_values($arrSchedule->where('workflow_id', $workflow->id)->toArray());
            
            $countTaskCompleted = count(array_filter($arrTask, function ($task) {
                return $task['status'] == 'completed';
            }));
            $idStage = Stage::where('workflow_id', $workflow->id)->whereNotIn('index', [0, 1])->get()->pluck('id');
            $countTaskFailed = count(array_filter($arrTask, function ($task) {
                return $task['status'] == 'overdue';
            }));
            $countTaskNotExpired = Task::where('expired', null)->whereIn('stage_id', $idStage)
                ->get()
                ->count();
            $workflow->count_task_completed = $countTaskCompleted;
            $workflow->count_task_not_expired = $countTaskNotExpired;
            $workflow->count_task_failed = $countTaskFailed;
        }

        return response()->json($workflows);
    }

    public function getScheduleWorkflow($start, $end)
    {
        $arrSchedule = [];
        $worflows = Workflow::all();
        $latestTaskIds = HistoryMoveTask::selectRaw('MAX(id) as id')
            ->whereNotNull('worker')
            ->whereNotNull('started_at')
            ->where('status', null)
            ->groupBy('old_stage', 'new_stage', 'worker', 'task_id')
            ->pluck('id');
        $accounts = Account::all();
        $taskInHistory = HistoryMoveTask::whereIn('id', $latestTaskIds)
            ->with(['oldStage', 'newStage', 'task'])
            ->whereDate('created_at', '>=', $start->format('Y-m-d'))
            ->whereDate('created_at', '<=', $end->format('Y-m-d'))
            ->get();
        $dayOff = Schedule::where('day_of_week', ">=", $start->format('Y-m-d'))
            ->where('day_of_week', "<=", $end->format('Y-m-d'))
            ->get();
        foreach ($taskInHistory as $task) {
            for ($date = clone $start; $date->lte(clone $end); $date->addDay()) {
                if ($task->oldStage) {
                    # code...
                }
                $thisDayOff = $dayOff->where('day_of_week', $date->format('Y-m-d'))->first();
                if ($thisDayOff->go_to_work == false) {
                    continue;
                }
                $completedAt = Carbon::parse($task->created_at);
                $expiredAt = Carbon::parse($task->expired_at);
                $startedAt = Carbon::parse($task->started_at);
                if (now()->toDateString() < $date->toDateString() || $date->toDateString() < $startedAt->toDateString() || $completedAt->toDateString() < $date->toDateString()) {
                    continue;
                }
                $taskCopy = clone $task;
                $taskCopy->account_id = $taskCopy->worker;
                if ($accounts->where('id', $taskCopy->worker)->first()->avatar !== null) {
                    $taskCopy->avatar = env('APP_URL') . $accounts->where('id', $taskCopy->worker)->first()->avatar;
                }
                $taskCopy->status = 'in_progress';
                if ($completedAt->isSameDay($date)) {
                    if ($taskCopy->expired_at == null || $completedAt->lessThan($expiredAt)) {
                        $taskCopy->status = 'completed';
                    } else {
                        $taskCopy->status = 'completed_late';
                    }
                }
                $taskCopy->workflow_name = $worflows->where('id', $taskCopy->oldStage->workflow_id)->first()->name;
                $taskCopy->workflow_id = $taskCopy->oldStage->workflow_id;
                $taskCopy->stage_name = $taskCopy->oldStage->name;
                $taskCopy->name_task = $taskCopy->task->name;
                $hoursWork = $this->getHoursWork($taskCopy, $date);
                $taskCopy->hours_work = $hoursWork['hours_work'];
                $taskCopy->start = $hoursWork['start']->format("Y-m-d H:i:s");
                $taskCopy->end = $hoursWork['end']->format("Y-m-d H:i:s");
                unset($taskCopy->worker);
                unset($taskCopy->oldStage);
                unset($taskCopy->task);
                $arrSchedule[] = $taskCopy;
            }
        }

        return collect($arrSchedule);
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

        return ['hours_work' => number_format($hoursWork, 2), 'start' => $start, 'end' => $end];
    }
}
