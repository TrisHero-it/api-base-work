<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountDepartment;
use App\Models\AccountWorkflow;
use App\Models\HistoryMoveTask;
use App\Models\Schedule;
use App\Models\Task;
use App\Models\Workflow;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ScheduleWorkController extends Controller
{
    public function index(Request $request)
    {
        $globalBan = [11, 12, 14, 15, 17, 25];
        if (isset($request->end)) {
            $startDate = Carbon::parse($request->start);
            $endDate = Carbon::parse($request->end);
        } else {
            $endDate = Carbon::now()->endOfWeek();
            $startDate = Carbon::now()->startOfWeek();
        }
        $worflows = Workflow::all();

        if ($request->filled('department_id')) {
            $accountDepartments = AccountDepartment::where('department_id', $request->department_id)
                ->pluck('account_id');
            $accounts = Account::whereIn('id', $accountDepartments)
                ->whereNotIn('id', $globalBan)
                ->get();
        } else {
            $accounts = Account::query()
                ->whereNotIn('id', $globalBan)
                ->get();
        }
        $taskInProgress = Task::select('id as task_id', 'name as name_task', 'account_id', 'started_at', 'expired as expired_at', 'stage_id', 'completed_at')
            ->whereNotIn('account_id', $globalBan)
            ->with(['stage', 'account'])
            ->whereIn('account_id', $accounts->pluck('id'))
            ->where('started_at', '!=', null)
            ->get();
        $arrSchedule = [];
        $dayOff = Schedule::where('day_of_week', ">=", $startDate->format('Y-m-d'))
            ->where('day_of_week', "<=", $endDate->format('Y-m-d'))
            ->get();
        // Lấy các công việc đang tiến hành
        foreach ($taskInProgress as $task) {
            for ($date = clone $startDate; $date->lte(clone $endDate); $date->addDay()) {
                $taskCopy = clone $task;
                $thisDayOff = $dayOff->where('day_of_week', $date->format('Y-m-d'))->first();
                if ($date->toDateString() < Carbon::parse($taskCopy->started_at)->toDateString() || $thisDayOff->go_to_work == false) {
                    continue;
                }
                if (!now()->lessThan($date)) {
                    $hoursWork = $this->getHoursWork($taskCopy, date: $date);
                    $taskCopy->hours_work = $hoursWork['hours_work'];
                    $taskCopy->start = $hoursWork['start']->format("Y-m-d H:i:s");
                    $taskCopy->end = $hoursWork['end']->format("Y-m-d H:i:s");
                    if ($taskCopy->stage_id != null) {
                        $taskCopy->stage_name = $taskCopy->stage->name;
                        $taskCopy->workflow_name = $worflows->where('id', $taskCopy->stage->workflow_id)->first()->name;
                        $taskCopy->workflow_id = $taskCopy->stage->workflow_id;
                        unset($taskCopy->stage);
                    }
                    $taskCopy->status = 'in_progress';
                    if ($taskCopy->expired_at != null) {
                        if (Carbon::parse($taskCopy->expired_at)->lessThan($date)) {
                            $taskCopy->status = 'overdue';
                        }
                    }
                    $taskCopy->avatar = env('APP_URL') . $taskCopy->account->avatar;
                    unset($taskCopy->account);
                    $arrSchedule[] = $taskCopy;
                }
            }
        }
        // Lấy các công việc đã hoàn thành hoặc là thất bại
        $latestTaskIds = HistoryMoveTask::selectRaw('MAX(id) as id')
            ->whereNotNull('worker')
            ->whereNotNull('started_at')
            ->where('status', null)
            ->groupBy('old_stage', 'new_stage', 'worker', 'task_id')
            ->pluck('id');


        $taskInHistory = HistoryMoveTask::whereIn('id', $latestTaskIds)
            ->with(['oldStage', 'newStage', 'task'])
            ->whereDate('created_at', '>=', $startDate->format('Y-m-d'))
            ->whereDate('created_at', '<=', $endDate->format('Y-m-d'))
            ->whereIn('worker', $accounts->pluck('id'))
            ->whereNotIn('worker', $globalBan)
            ->get();
        foreach ($taskInHistory as $task) {
            for ($date = clone $startDate; $date->lte(clone $endDate); $date->addDay()) {
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
                if ($expiredAt->lt($date)) {
                    $taskCopy->status = 'overdue';
                }
                if ($completedAt->isSameDay($date)) {
                    if ($taskCopy->expired_at == null || $completedAt->lessThan($expiredAt)) {
                        $taskCopy->status = 'completed';
                    } else {
                        $taskCopy->status = 'completed_late';
                    }
                }
                $taskCopy->workflow_name = $worflows->where('id', $taskCopy->oldStage->workflow_id)->first()->name;
                $taskCopy->workflow_id = $taskCopy->oldStage->workflow_id;
                $taskCopy->stage_id = $taskCopy->oldStage->id;
                $taskCopy->stage_name = $taskCopy->oldStage->name;
                $taskCopy->name_task = $taskCopy->task->name;
                $hoursWork = $this->getHoursWorkHistory($taskCopy, $date);
                $taskCopy->hours_work = $hoursWork['hours_work'];
                $taskCopy->start = $hoursWork['start']->format("Y-m-d H:i:s");
                $taskCopy->end = $hoursWork['end']->format("Y-m-d H:i:s");
                unset($taskCopy->worker);
                unset($taskCopy->oldStage);
                unset($taskCopy->task);
                $arrSchedule[] = $taskCopy;
            }
        }

        return $arrSchedule;
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
