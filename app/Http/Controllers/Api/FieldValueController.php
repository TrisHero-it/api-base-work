<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TaskFieldStoreRequest;
use App\Http\Requests\TaskFieldUpdateRequest;
use App\Models\Field;
use App\Models\FieldTask;
use Illuminate\Http\Request;

class FieldValueController extends Controller
{
    public function index(Request $request)
    {
        $fields = Field::query()->select('id', 'name', 'type', 'require','options')->where('workflow_id', $request->workflow_id)->where('model', 'field')->get();
        foreach ($fields as $field) {
            $a = FieldTask::query()->select('value')->where('fields_id', $field->id)->where('task_id', $request->task_id)->first();
            $field['value'] = null;
            if (isset($a)) {
                if ($field->type == 'list') {
                    $field['value'] = json_decode($a->value, true);
                } else {
                    $field['value'] = $a->value;
                }
            }
        }

        return response()->json($fields);
    }

    public function store(TaskFieldStoreRequest $request)
    {
        try {
            FieldTask::query()->create($request->all());
            return response()->json(['success' => 'Thêm thành công']);
        } catch (\Exception $exception) {
            return response()->json(['error' => 'Lỗi tùm lum'], 500);
        }
    }

    public function update(TaskFieldUpdateRequest $request, $id)
    {
        try {
            $arr = $request->value;
            if (is_array($arr)) {
                $value = json_encode($arr, true);
            } else {
                $value =  $request->get('value');
            }

            $data = $request->except('data_yield_id');
            $data['data_yield_id'] = $request->get('field_id');
            $field = FieldTask::query()->where('data_yield_id', $request->get('field_id'))->where('task_id', $request->task_id)->first();
            if (isset($field)) {
                $field->update($data);
            } else {
                $field = Field::query()->where('id', $request->get('field_id'))->first();
                if (isset($field)) {
                    FieldTask::query()->create([
                        'data_yield_id' => $request->get('field_id'),
                        'task_id' => $request->get('task_id'),
                        'value' => $value
                    ]);
                } else {
                    abort(404);
                }
            }
            return response()->json([
                'success' => 'Thành công'
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'error' => 'Đã xảy ra lỗi'
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $field = FieldTask::query()->findOrFail($id);
            $field->delete();
            return response()->json([
                'success' => 'Xoá thành công'
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'error' => 'Đã xảy ra lỗi'
            ], 500);
        }
    }
}
