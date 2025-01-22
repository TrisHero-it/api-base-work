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
        // Xác định ngày bắt đầu và kết thúc
        if (isset($request->end)) {
            $startDate = Carbon::parse($request->start);
            $endDate = Carbon::parse($request->end);
        } else {
            $endDate = Carbon::now()->endOfWeek();
            $startDate = Carbon::now()->startOfWeek();
        }

        // Lấy toàn bộ thông tin liên quan một lần
        $accounts = Account::query()->select('id', 'full_name', 'avatar')->get()->keyBy('id');
        $stages = Stage::query()->select('id', 'name')->get()->keyBy('id');

        // Lấy tất cả các Task cần thiết
        $tasks = Task::query()
            ->select('id as task_id', 'name as name_task', 'account_id', 'started_at', 'expired as expired_at', 'stage_id', 'completed_at')
            ->where('account_id', '!=', null)
            ->whereDate('started_at', '<=', $endDate)
            ->where(function ($query) use ($endDate) {
                $query->whereNotNull('completed_at')
                    ->whereDate('completed_at', '>=', $endDate)
                    ->orWhere(function ($subQuery) use ($endDate) {
                        $subQuery->whereNull('completed_at')
                            ->where(function ($subSubQuery) use ($endDate) {
                                $subSubQuery->whereDate('expired', '>=', $endDate)
                                    ->orWhereNull('expired');
                            });
                    });
            })
            ->orderBy('expired_at')
            ->get();

        // Lấy tất cả History Move Tasks cần thiết
        $historyTasks = DB::table('history_move_tasks')
            ->select('task_id', 'old_stage', 'worker', 'started_at', 'expired_at', 'created_at')
            ->where('worker', '!=', null)
            ->whereDate('started_at', '<=', $endDate)
            ->where(function ($query) use ($endDate) {
                $query->whereDate('expired_at', '>=', $endDate)
                    ->orWhereNull('expired_at');
            })
            ->get();

        // Tạo mảng dữ liệu
        $arr = [];
        for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
            $currentDate = $date->format('Y-m-d');

            // Lọc dữ liệu Task theo ngày
            $filteredTasks = $tasks->filter(function ($task) use ($currentDate) {
                return Carbon::parse($task->started_at)->lte($currentDate);
            })->map(function ($task) use ($currentDate, $accounts, $stages) {
                $task->account_name = $accounts[$task->account_id]->full_name ?? null;
                $task->avatar = $accounts[$task->account_id]->avatar ?? null;
                $task->stage_name = $stages[$task->stage_id]->name ?? null;

                // Xác định trạng thái
                if ($task->expired_at === null) {
                    $task->status = $task->completed_at && Carbon::parse($task->completed_at)->isSameDay($currentDate)
                        ? 'completed'
                        : 'in_progress';
                } else {
                    $task->status = Carbon::parse($task->expired_at)->isFuture() ? 'in_progress' : 'failed';
                }

                unset($task->account_id, $task->stage_id);
                return $task;
            });

            // Lọc và xử lý history tasks
            $filteredHistory = $historyTasks->filter(function ($history) use ($currentDate) {
                return Carbon::parse($history->created_at)->format('Y-m-d') === $currentDate;
            })->map(function ($history) use ($accounts, $stages) {
                $history->name_task = Task::find($history->task_id)->name ?? null;
                $history->stage_name = $stages[$history->old_stage]->name ?? null;
                $worker = $accounts[$history->worker] ?? null;
                $history->account_name = $worker->full_name ?? null;
                $history->avatar = $worker->avatar ?? null;

                // Xác định trạng thái
                $history->status = Carbon::parse($history->started_at)->lte($history->expired_at) ? 'in_progress' : 'failed';
                unset($history->worker, $history->old_stage);
                return $history;
            });

            // Kết hợp dữ liệu
            $arr[$currentDate] = array_merge($filteredTasks->toArray(), $filteredHistory->toArray());
        }

        return $arr;
    }
}
