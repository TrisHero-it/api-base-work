<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Field;
use App\Models\FieldTask;
use App\Models\Task;
use Illuminate\Http\Request;

class TaskReportController extends Controller
{
    public function index($id, Request $request)
    {
        $fields = Field::query()->where('stage_id', $id)->get();
        $tasks = Task::query()->where('stage_id', $id)->orderBy('id', 'desc')->get();

        $b = 0;
        $arrMerge  = [];
        foreach ($tasks as $task) {
            $arr = [];
            foreach ($fields as $field) {
                $a = FieldTask::query()->where('task_id', $task->id)->where('fields_id', $field->id)->first();

                if ($a == null) {
                    break;
                }else {
                    if ($b == 0) {
                        $arr= array_merge([
                            'Người thực thi' => $a->account->username,
                            'Tên nhiệm vụ'=> $a->task->name,
                        ], $arr) ;
                    }
                    $arr = array_merge([
                        $field->name => $a->value,
                    ], $arr) ;
                }
            }
            if ($arr != null) {
                $arrMerge[] = $arr;
            }
        }
        return response()->json($arrMerge);


    }

    public function store(Request $request, int $id)
    {
        try {
            $task = Task::query()->where('code', $id)->first();
            $data = $request->except('account_id');
            foreach ($data as $field => $value) {
                $a = FieldTask::query()->where('fields_id', $field)->where('task_id', $task->id)->first();
                if (isset($a)) {
                    $a->update([
                        'value' => $value,
                    ]);
                }else {
                    $b = explode(' ', $request->header('Authorization'));
                    $acc = Account::query()->where('remember_token', $b[1])->first();
                    FieldTask::query()->create([
                        'value' => $value,
                        'model' => 'report-field',
                        'fields_id' => $field,
                        'task_id' => $task->id,
                        'account_id'=> $acc->id,
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

