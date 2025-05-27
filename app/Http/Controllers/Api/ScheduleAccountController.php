<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HistoryMoveTask;
use App\Models\Task;
use App\Models\Workflow;
use Illuminate\Http\Request;
use App\Models\Account;
use App\Models\AccountDepartment;
use App\Models\Department;
use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class ScheduleAccountController extends Controller
{
    public function index(Request $request)
    {
        $globalBan = [11, 12];
        if (Auth::user()->isSeniorAdmin()) {
            $accounts = Account::select('email', 'full_name', 'avatar', 'id')
                ->with('department')
                ->whereNotIn('id', $globalBan)
                ->where('quit_work', false);
            if (isset($request->department_id)) {
                $accountDepartment = AccountDepartment::where('department_id', $request->department_id)
                    ->get()
                    ->pluck('account_id');
                $accounts = $accounts->whereIn('id', $accountDepartment);
            }
            $accounts = $accounts->get();
        } else {
            $accountsDepartment = AccountDepartment::where('account_id', Auth::user()->id)->get()->pluck('department_id');
            $accountsDepartment = AccountDepartment::whereIn('department_id', $accountsDepartment)
                ->get()
                ->pluck('account_id');
            $accounts = Account::select('email', 'full_name', 'avatar', 'id')
                ->where('quit_work', false)
                ->with('department')
                ->whereIn('id', $accountsDepartment)
                ->whereNotIn('id', $globalBan)
                ->get();
        }
        if (isset($request->date)) {
            $date = $request->date;
        } else {
            $date = now()->format('Y-m-d');
        }
        $dayOff = Schedule::whereDate('day_of_week', $date)->first();

        $data = $this->getScheduleAccount($date, $date);
        $departments = AccountDepartment::query()->get();

        foreach ($accounts as $account) {
            if ($departments->where('account_id', $account->id)->first() != null) {
                $account->department_id = $departments->where('account_id', $account->id)->first()->department_id;
            } else {
                $account->department_id = null;
            }
            if (count($account->department) != 0) {
                $account->department_name = $account->department[0]->name;
                unset($account->department);
            }
            $hoursWork = 0;
            if ($dayOff->go_to_work != false) {
                $newData = array_filter($data, function ($item) use ($account) {
                    return $item['account_id'] == $account->id;
                });
                $newData = array_values($newData);
                usort($newData, function ($a, $b) {
                    return strtotime($a['start']) - strtotime($b['start']);
                });
                $merged = [];
                foreach ($newData as $range) {
                    if (empty($merged) || strtotime(end($merged)['end']) < strtotime($range['start'])) {
                        $merged[] = $range;
                    } else {
                        // Hợp nhất khoảng thời gian bị trùng
                        $merged[count($merged) - 1]['end'] = max(end($merged)['end'], $range['end']);
                    }
                }
                $innerStart1 = Carbon::parse($date . " 08:30:00");
                $innerEnd1 = Carbon::parse($date . " 12:00:00");
                $innerStart2 = Carbon::parse($date . " 13:30:00");
                $innerEnd2 = Carbon::parse($date . " 17:30:00");
                foreach ($merged as $range) {
                    if ($innerStart1->greaterThanOrEqualTo(Carbon::parse($range['start'])) && $innerEnd1->lessThanOrEqualTo(Carbon::parse($range['end']))) {
                        $hoursWork = $hoursWork + number_format(3.5, 3);
                    } else {
                        $validStart = max($innerStart1, Carbon::parse($range['start']));
                        $validEnd = min($innerEnd1, $range['end']);
                        if ($validStart->lessThan($validEnd)) {
                            $validHours = $validStart->floatDiffInHours($validEnd, true);
                            $hoursWork += number_format($validHours, 3);
                        }
                    }

                    if ($innerStart2->greaterThanOrEqualTo(Carbon::parse($range['start'])) && $innerEnd2->lessThanOrEqualTo(Carbon::parse($range['end']))) {
                        $hoursWork = $hoursWork + number_format(4, 3);
                    } else {
                        $validStart = max($innerStart2, Carbon::parse($range['start']));
                        $validEnd = min($innerEnd2, $range['end']);
                        if ($validStart->lessThan($validEnd)) {
                            $validHours = $validStart->floatDiffInHours($validEnd, true);
                            $hoursWork += number_format($validHours, 3);
                        }
                    }
                }
            }
            $account['hours_work'] = number_format($hoursWork, 2);
        }

        return response()->json($accounts);
    }

    public function getScheduleAccount($start = null, $end = null)
    {
        if (isset($end)) {
            $startDate = Carbon::parse($start);
            $endDate = Carbon::parse($end);
        } else {
            $endDate = Carbon::now()->endOfWeek();
            $startDate = Carbon::now()->startOfWeek();
        }
        $worflows = Workflow::all();
        $taskInProgress = Task::select('id as task_id', 'name as name_task', 'account_id', 'started_at', 'expired as expired_at', 'stage_id', 'completed_at')
            ->with(['stage', 'account'])
            ->where('account_id', '!=', null)
            ->where('started_at', '!=', null)
            ->get();
        $arrSchedule = [];
        // Lấy các công việc đang tiến hành
        foreach ($taskInProgress as $task) {
            for ($date = clone $startDate; $date->lte(clone $endDate); $date->addDay()) {
                $taskCopy = clone $task;
                if ($date->toDateString() < Carbon::parse($taskCopy->started_at)->toDateString()) {
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
                        unset($taskCopy->stage);
                    }
                    $taskCopy->status = 'in_progress';
                    if ($taskCopy->expired_at != null) {
                        if (Carbon::parse($taskCopy->expired_at)->lessThan($date)) {
                            $taskCopy->status = 'overdue';
                        }
                    }
                    $taskCopy->avatar = $taskCopy->account->avatar;
                    unset($taskCopy->account);
                    $arrSchedule[] = $taskCopy;
                }
            }
        }
        // Lấy các công việc đã hoàn thành hoặc là thất bại
        $latestTaskIds = HistoryMoveTask::selectRaw('MAX(id) as id')
            ->whereNotNull('worker')
            ->whereNotNull('started_at')
            ->groupBy('old_stage', 'new_stage', 'worker', 'task_id')
            ->pluck('id');
        $accounts = Account::where('quit_work', false)->get();
        $taskInHistory = HistoryMoveTask::whereIn('id', $latestTaskIds)
            ->with(['oldStage', 'newStage', 'task'])
            ->whereDate('created_at', '>=', $startDate->format('Y-m-d'))
            ->whereDate('created_at', '<=', $endDate->format('Y-m-d'))
            ->get();
        foreach ($taskInHistory as $task) {
            for ($date = clone $startDate; $date->lte(clone $endDate); $date->addDay()) {
                $completedAt = Carbon::parse($task->created_at);
                $expiredAt = Carbon::parse($task->expired_at);
                $startedAt = Carbon::parse($task->started_at);
                if (now()->toDateString() < $date->toDateString() || $date->toDateString() < $startedAt->toDateString() || $completedAt->toDateString() < $date->toDateString()) {
                    continue;
                }
                $taskCopy = clone $task;
                $taskCopy->account_id = $taskCopy->worker;
                if ($accounts->where('id', $taskCopy->worker)->first()->avatar !== null) {
                    $taskCopy->avatar = $accounts->where('id', $taskCopy->worker)->first()->avatar;
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
