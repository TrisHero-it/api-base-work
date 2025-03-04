<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TaskFieldStoreRequest;
use App\Http\Requests\TaskFieldUpdateRequest;
use App\Models\Account;
use App\Models\Field;
use App\Models\FieldTask;
use App\Models\Stage;
use App\Models\Task;
use Illuminate\Http\Request;

class ReportFieldValueController extends Controller
{

    public function store(Request $request)
    {
        $task = Task::query()->findOrFail($request->task_id);
        if ($task == null) {
            return response()->json([
                'error' => 'Không tìm thấy nhiệm vụ'
            ]);
        }
        $data = $request->except('account_id', 'task_id');
        foreach ($data as $field => $value) {
            if ($value == null) {
                continue;
            }
            $a = FieldTask::query()->where('fields_id', $field)->where('task_id', $task->id)->first();
            if (isset($a)) {
                $a->update([
                    'value' => $value,
                ]);
            } else {
                FieldTask::query()->create([
                    'value' => $value,
                    'model' => 'report-field',
                    'fields_id' => $field,
                    'task_id' => $task->id,
                    'account_id' => $task->account_id,
                ]);
            }
        }
        return response()->json(['success' => 'Thêm thành công']);
    }

    public function index(Request $request)
    {
        $arrTask = [];
        $arrCondition = [];
        //      Lấy ra các trường thuộc giai đoạn í
        $field = Field::query()->where('stage_id', $request->stage_id)->first();
        while (true) {
            if ($field == null) {
                break;
            }
            if (!isset($g)) {
                $g = $field->id;
            }
            $query = FieldTask::query();
            //           Lấy ra các giá trị từ bảng fieldtask theo fields_id
            $query->where('fields_id', $g);
            $query->where('model', 'report-field');
            foreach ($arrCondition as $condition) {
                $query->where('task_id', '!=', $condition);
            }
            $a = $query->first();
            if ($a == null) {
                break;
            }
            //          Lấy ra tất cả nhiệm vụ từ giá trị mình vừa lấy ở trên
            $tasks = FieldTask::query()->where('task_id', $a->task_id)->where('fields_id', $g)->get();
            //          Thêm giá trị vừa rồi vào để lọc cho vòng lặp sau
            $arrCondition[] = $a->task_id;
            $b = [];
            $c = 0;
            foreach ($tasks as $task) {
                $date2 = new \DateTime($task->created_at);
                $formattedDate = $date2->format('d/m/Y H:i:s');
                $date = ['Ngày tạo' => $formattedDate];
                if ($c == 0) {
                    $d = [
                        'Người thực thi' => $task->account->username,
                        'Tên nhiệm vụ' => $task->task->name,
                    ];
                    $b = array_merge($b, $d);
                }
                $c = 1;
                $task = [
                    $task->field->name => $task->value,
                ];
                $b = array_merge($b, $task);
            }
            $arrTask[] = array_merge($b, $date);
        }
        usort($arrTask, function ($a, $b) {
            $dateA = \DateTime::createFromFormat('d/m/Y H:i:s', $a['Ngày tạo']);
            $dateB = \DateTime::createFromFormat('d/m/Y H:i:s', $b['Ngày tạo']);
            return $dateB <=> $dateA;
        });

        return response()->json($arrTask);
    }

    public function update(int $id, TaskFieldUpdateRequest $request)
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

    public function show(int $id)
    {
        $task = Task::query()->findOrFail($id);
        $stages = Stage::query()->select('id', 'name')->where('workflow_id', $task->stage->workflow_id)->where('index', '!=', 0)->where('index', '!=', 1)->orderBy('index', 'asc')->get();
        foreach ($stages as $stage) {
            $a = [];
            $fields = Field::query()->select('id', 'name')->where('stage_id', $stage->id)->get();
            foreach ($fields as $field) {
                $report = FieldTask::query()->select('id', 'value', 'fields_id')->where('fields_id', $field->id)->where('task_id', $task->id)->first();
                if (isset($report)) {
                    $report['name_field'] = $field->name;
                    unset($report['field']);
                    unset($report['fields_id']);
                    $a[] = $report;
                }
            }
            $stage['fields'] = $a;
        }

        return response()->json($stages);
    }

    public function destroy(int $id)
    {
        try {
            FieldTask::query()->where('id', $id)->delete();
            return response()->json([
                'success' => 'Xoá thành công'
            ]);
        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 500);
        }
    }
}
