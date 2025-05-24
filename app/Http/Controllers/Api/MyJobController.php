<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kpi;
use App\Models\Stage;
use App\Models\Task;
use Auth;
use Illuminate\Http\Request;
use Notification;

class MyJobController extends Controller
{
    public function index(Request $request)
    {
        if (isset($request->include)) {
            $countMyTask = Task::where('account_id', Auth::id())
                ->where('stage_id', null)
                ->whereNull('completed_at')
                ->count();

            return response()->json($countMyTask);
        }
        $query = Task::with(['stage.workflow', 'account', 'creatorBy']);


        // Sắp xếp theo bộ lọc được chọn
        $sortableColumns = ['updated_at', 'created_at', 'expired', 'completed_at'];
        if (!empty($request->sort) && in_array($request->order, $sortableColumns)) {
            $query->orderBy($request->order, 'desc');
        }

        // Lọc theo người tạo
        if (!empty($request->created_by)) {
            $query->where('creator_by', $request->created_by);
        }

        // Lọc theo thời hạn (expired)
        if (!empty($request->start_expired) || !empty($request->end_expired)) {
            $query->whereBetween('expired', [
                $request->start_expired ?? '1970-01-01',
                $request->end_expired ?? now()
            ]);
        }

        // Lọc theo ngày tạo (created_at)
        if (!empty($request->start_created_at) || !empty($request->end_created_at)) {
            $query->whereBetween('created_at', [
                $request->start_created_at ?? '1970-01-01',
                $request->end_created_at ?? now()
            ]);
        }

        // Lọc theo ngày hoàn thành (completed_at)
        if (!empty($request->start_completed_at) || !empty($request->end_completed_at)) {
            $query->whereBetween('completed_at', [
                $request->start_completed_at ?? '1970-01-01',
                $request->end_completed_at ?? now()
            ]);
        }

        // Lọc theo tài khoản đang đăng nhập & chỉ lấy task chưa hoàn thành
        $tasks = $query->where('account_id', Auth::id())
            ->whereNull('completed_at')
            ->get();
        foreach ($tasks as $task) {
            if ($task->creator_by != null) {
                $task['creator_name'] = $task->creatorBy->full_name;
                $task['creator_avatar'] = env('APP_URL') . $task->creatorBy->avatar;
                unset($task->creatorBy);
            }
            if ($task->started_at != null) {
                $task['status'] = 'in_progress';
            }

            if ($task->expired != null) {
                if ($task->expired < now()) {
                    $task['status'] = 'overdue';
                }
            }
            if ($task->stage_id != null) {
                $task['stage_name'] = $task->stage->name;
                $task['workflow_name'] = $task->stage->workflow->name;
                $task['workflow_id'] = $task->stage->workflow->id;
                unset($task->stage);
                unset($task->workflow);
            }

            $task['account_name'] = $task->account->full_name;
            $task['account_avatar'] = env('APP_URL') . $task->account->avatar;
            unset($task->account);
        }

        return response()->json($tasks);
    }

    public function store(Request $request)
    {
        $data = $request->except('stage_id', 'expired');
        $data['expired'] = new \DateTime($request->expired_at);
        $data['started_at'] = now();
        $task = Task::query()->create($data);

        return response()->json($task);
    }

    public function update(int $id, Request $request)
    {
        $task = Task::query()->find($id);
        $data = $request->except('stage_id');
        if (isset($request->success)) {
            Kpi::query()->create([
                'task_id' => $id,
                'account_id' => $task->account_id
            ]);
        }
        $data['status'] = 'completed';
        $data['completed_at'] = now();
        $task->update($data);

        return response()->json($task);
    }
}
