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
        $dates = $this->getDateRange($request);
        $taskData = [];

        for ($date = $dates['start']; $date->lte($dates['end']); $date->addDay()) {
            $tasks = $this->getTasksForDate($date);
            $historyTasks = $this->getHistoryTasksForDate($date);
            $taskData[$date->format('Y-m-d')] = array_merge($tasks, $historyTasks);
        }

        return $taskData;
    }

    private function getDateRange(Request $request)
    {
        return $request->has('end') ? [
            'start' => Carbon::parse($request->start),
            'end' => Carbon::parse($request->end),
        ] : [
            'start' => Carbon::now()->startOfWeek(),
            'end' => Carbon::now()->endOfWeek(),
        ];
    }

    private function getTasksForDate($date)
    {
        $tasks = Task::with(['stage:id,name', 'account:id'])
            ->select('id as task_id', 'name as name_task', 'account_id', 'started_at', 'expired as expired_at', 'stage_id', 'completed_at')
            ->whereNotNull('account_id')
            ->whereDate('started_at', '<=', $date)
            ->where(function ($query) use ($date) {
                $query->whereDate('completed_at', '>=', $date)
                    ->orWhere(function ($subQuery) use ($date) {
                        $subQuery->whereNull('completed_at')
                            ->where(function ($subSubQuery) use ($date) {
                                $subSubQuery->whereDate('expired', '>=', $date);
                                if ($date->gt(now())) {
                                    $subSubQuery->whereNotNull('expired');
                                } else {
                                    $subSubQuery->orWhereNull('expired');
                                }
                            });
                    });
            })
            ->orderBy('expired_at')
            ->get();

        return $tasks->map(fn($task) => [
            'task_id' => $task->task_id,
            'name_task' => $task->name_task,
            'account_id' => $task->account_id,
            'started_at' => $task->started_at,
            'expired_at' => $task->expired_at,
            'hours_work' => $this->getHoursWork($task, $date)['hours_work'],
            'stage_name' => optional($task->stage)->name,
            'status' => $this->getTaskStatus($task, $date)
        ])->toArray();
    }

    private function getHistoryTasksForDate($date)
    {
        $historyTasks = HistoryMoveTask::whereDate('started_at', '<=', $date)
            ->whereDate('created_at', '>=', $date)
            ->whereNotNull('worker')
            ->where(function ($query) use ($date) {
                $query->whereDate('expired_at', '>=', $date)
                    ->orWhereNull('expired_at');
            })
            ->with(['task:id,name', 'worker:id,avatar', 'oldStage:id,name'])
            ->get();

        return $historyTasks->map(fn($his) => [
            'task_id' => $his->task_id,
            'name_task' => optional($his->task)->name,
            'stage_name' => optional($his->oldStage)->name,
            'account_id' => optional($his->worker)->id,
            'avatar' => optional($his->worker)->avatar,
            'started_at' => $his->started_at,
            'expired_at' => $his->expired_at,
            'hours_work' => $this->getHoursWorkHistory($his, $date)['hours_work'],
            'status' => $this->getHistoryTaskStatus($his, $date)
        ])->toArray();
    }

    private function getTaskStatus($task, $date)
    {
        return is_null($task->expired_at)
            ? (is_null($task->completed_at) || Carbon::parse($task->completed_at)->isSameDay($date) ? 'in_progress' : 'completed')
            : (Carbon::parse($task->expired_at)->greaterThan(Carbon::now()) ? 'in_progress' : 'failed');
    }

    private function getHistoryTaskStatus($his, $date)
    {
        return ($his->started_at < $his->expired_at || (!is_null($his->worker) && is_null($his->expired_at)))
            ? (Carbon::parse($his->created_at)->isSameDay($date) ? 'completed' : 'in_progress')
            : 'failed';
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

    public function getHoursWorkHistory($his, $date)
    {
        $hoursWork = 0;
        if (Carbon::parse($his->started_at)->format('Y-m-d') == $date->format('Y-m-d')) {
            $start = Carbon::parse($his->started_at);
        } else {
            $start = Carbon::parse($date->format("Y-m-d") . " 08:30:00");
        }
        if (Carbon::parse($his->created_at)->format('Y-m-d') == $date->format('Y-m-d')) {
            $end = Carbon::parse($his->created_at);
        } else {
            $end = Carbon::parse($date)->setTime(17, 30);
        }
        $innerStart1 = Carbon::parse($date->format("Y-m-d") . " 08:30:00");
        $innerEnd1 = Carbon::parse($date->format("Y-m-d") . " 12:00:00");
        $innerStart2 = Carbon::parse($date->format("Y-m-d") . " 13:30:00");
        $innerEnd2 = Carbon::parse($date->format("Y-m-d") . " 17:30:00");
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
