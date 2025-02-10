<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kpi;
use App\Models\Task;
use Illuminate\Http\Request;

class MyJobController extends Controller
{
    public function index(Request $request)
    {
        $tasks = Task::query()->with(['stage.workflow', 'account']);
        if (isset($request->account_id)) {
            $tasks = $tasks->where('account_id', $request->account_id)->where('completed_at', null);
        } else {
            $tasks = $tasks->where('account_id', '!=', null)->where('completed_at', null);
        }
        $tasks = $tasks->get();
        foreach ($tasks as $task) {
            if ($task->stage_id != null) {
                $task['stage_name'] = $task->stage->name;
                $task['workflow_name'] = $task->stage->workflow->name;
                unset($task->stage);
                unset($task->workflow);
            }
            $task['account_name'] = $task->account->full_name;
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
                'account_id'=> $task->account_id
            ]);
        }
        $data['status'] = 'completed';
        $data['completed_at'] = now();
        $task->update($data);

        return response()->json($task);
    }
}
