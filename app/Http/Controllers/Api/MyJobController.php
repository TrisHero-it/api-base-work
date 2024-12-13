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
        $tasks = Task::query();
        if (isset($request->account_id)) {
            $tasks = $tasks->where('account_id', $request->account_id);
        } else {
            $tasks = $tasks->where('account_id', '!=', null);
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
        $data['expired'] = new \DateTime($request->expired);
        $data['code'] =  rand(10000000, 99999999);
        $data['started_at'] = now();
        $task = Task::query()->create($data);

        return response()->json($task);
    }

    public function update(int $id, Request $request)
    {
        $task = Task::query()->find($id);
        if (isset($request->success)) {
            Kpi::query()->create([
                'task_id' => $id,
                'account_id'=> $task->account_id
            ]);
        }
        $data = $request->except('stage_id');
        $data['status'] = 'completed';
        $task->update($data);

        return response()->json($task);
    }
}
