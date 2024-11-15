<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Field;
use App\Models\FieldTask;
use Illuminate\Http\Request;

class TaskReportController extends Controller
{
    public function index($id, Request $request)
    {
        $arrTask = [];
        $arrCondition = [];
        $fields = Field::query()->where('stage_id', $id)->get();
        foreach ($fields as $field) {
            $query = FieldTask::query();
            $query->where('fields_id', $field->id);
            foreach ($arrCondition as $condition) {
                $query->where('task_id', '!=', $condition);
            }
            $a = $query->first();
            if ($a == null) {
                continue;
            }
            $tasks = FieldTask::query()->where('task_id', $a->task_id)->get();
            $arrCondition[] = $a->task_id;
            $b =[];
            $c =0;
        foreach ($tasks as $task) {
            if ($c ==0) {
                $d = [
                    'staff' => $task->account->username,
                    'task_name'=> $task->task->name,
                ];
                $b = array_merge($b,$d);
            }
            $c = 1;
            $task = [
                $task->field->name => $task->value,
            ];
            $b = array_merge($b, $task);
        }

            $arrTask[] = $b;
        }

        return response()->json($arrTask);
    }

    public function store(Request $request, int $id)
    {
        try {
            $data = $request->all();
            $index = 0;
            $total = count($data);
            foreach ($data as $field => $value) {
                $index++;
                if ($index == $total) {
                    break;
                }
                $a = FieldTask::query()->where('fields_id', $field)->where('task_id', $id)->first();
                if (isset($a)) {
                    $a->update([
                        'value' => $value,
                    ]);
                }else {
                    FieldTask::query()->create([
                        'value' => $value,
                        'model' => 'report-field',
                        'fields_id' => $field,
                        'task_id' => $id,
                        'account_id'=> $request->account_id
                    ]);
                }
            }
            return response()->json(['success' => 'Thêm thành công']);
        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 500);
        }
    }

    public function update($id, Request $request)
    {
        try {
            $data = $request->all();
            foreach ($data as $field => $value) {
                FieldTask::query()->where('fields_id', $field)->where('task_id', $id)->update(['value' => $value]);
            }
            return response()->json([
                'success' => 'Sửa thành công'
            ]);
        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 500);
        }
    }

    public function destroy($id, Request $request)
    {
        try {
            FieldTask::query()->where('id', $id)->delete();
            return response()->json([
                'success' => 'Xoá thành công'
            ]);
        }catch (\Exception $exception){
            return response()->json(['error' => $exception->getMessage()], 500);
        }
    }

}

