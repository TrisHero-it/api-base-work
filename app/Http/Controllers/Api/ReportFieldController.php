<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReportFieldStoreRequest;
use App\Http\Requests\ReportFieldUpdateRequest;
use App\Models\Field;
use App\Models\FieldTask;
use App\Models\Task;
use Illuminate\Http\Request;

class ReportFieldController extends Controller
{
    public function store(Request $request)
    {
        try {
            $data = $request->except('options');
            $data['options'] = explode(',', $request->options);
            $data['model'] = 'report-field';
            Field::query()->create($data);
            return response()->json([
                'success' => 'Thêm thành công'
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'error' => $exception->getMessage()
            ], 500);
        }
    }

    public function index(Request $request)
    {
        if (isset($request->stage_id)) {
            if (isset($request->task_id)) {
                $reports = Field::query()->where('model', 'report-field')->where('stage_id', $request->stage_id)->get();
                foreach ($reports as $report) {
                    $task = Task::query()->findOrFail($request->task_id);
                    $a = FieldTask::query()->select('value')->where('fields_id', $report->id)->where('task_id', $task->id)->first();
                    if (isset($a)) {
                        $report['value'] = $a->value;
                    } else {
                        $report['value'] = null;
                    }
                }
            } else {
                $reports = Field::query()->where('model', 'report-field')->where('stage_id', $request->stage_id)->get();
            }
        } else {
            $reports = Field::query()->where('model', 'report-field')->get();
        }

        return response()->json($reports);
    }

    public function update(ReportFieldUpdateRequest $request, $id)
    {
        try {
            $report = Field::query()->findOrFail($id);
            $data = $request->all();
            $report->update($data);
            return response()->json([
                'success' => 'Sửa thành công'
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'error' => $exception->getMessage()
            ]);
        }
    }

    public function destroy($id)
    {
        try {
            $report = Field::query()->findOrFail($id);
            $report->delete();
            return response()->json([
                'success' => 'Xoá thành công'
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'error' => $exception->getMessage()
            ]);
        }
    }
}
