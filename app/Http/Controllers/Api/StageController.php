<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StageStoreRequest;
use App\Models\Stage;
use App\Models\Task;
use http\Client\Response;
use Illuminate\Http\Request;

class StageController extends Controller
{
    public function index($id)
    {
        $stages = Stage::query()->where('workflow_id', $id)->orderBy('index', 'desc')->get();
        foreach ($stages as $stage) {
            $tasks = Task::query()->where('stage_id', $stage['id'])->orderBy('updated_at', 'desc')->get();
            foreach ($tasks as $task) {
                $task['id'] = $task->code;
            }
            $stage['tasks'] = $tasks;
        }
        return response()->json($stages);
    }

    public function store(StageStoreRequest $request) {
        try {
            if (!isset($request->index)) {
                $stage = Stage::query()->where('workflow_id', $request->workflow_id)->orderByDesc('index')->first();
               $a = Stage::query()->create([
                    'name' => $request->name,
                    'workflow_id'=> $request->workflow_id,
                    'description' => $request->description ?? null,
                    'index'=> $stage->index+1,
                   'expired_after_hours'=> $request->expired_after_hours ?? null
                ]);
            }else {
                if (isset($request->right)){
                    $stages = Stage::query()->where('workflow_id', $request->workflow_id)
                        ->where('index', '>=', $request->index)
                        ->get();

                    foreach ($stages as $stage) {
                        $stage->update([
                            'index' => $stage->index+1
                        ]);
                    }

                    $a =   Stage::create([
                        'name' => $request->name,
                        'workflow_id'=> $request->workflow_id,
                        'description' => $request->description,
                        'index'=> $request->index
                    ]);

                }else {

                    $stages = Stage::query()->where('workflow_id', $request->workflow_id)
                        ->where('index', '>', $request->index)
                        ->get();

                    foreach ($stages as $stage) {
                        $stage->update([
                            'index' => $stage->index+1
                        ]);
                    }

                 $a =  Stage::create([
                        'name' => $request->name,
                        'workflow_id'=> $request->workflow_id,
                        'description' => $request->description,
                        'index'=> $request->index+1
                    ]);

                }
            }
            return response()->json([
                'success' => 'Thêm thành công',
                'id'=> $a->id
            ]);
        }catch (\Exception $exception){
            return response()->json(['error' => 'Đã xảy ra lỗi '. $exception->getMessage()], 500);
        }
    }

    public function update(Request $request, $id) {
        try {
            $stage = Stage::query()->findOrFail($id);
            $stage->update($request->all());
            return \response()->json([
                'success' => 'Sửa thành công'
            ]);
        }catch (\Exception $exception){
            return response()->json([
                'error' => 'Đã xảy ra lỗi'
            ], 500);
        }
    }

    public function destroy(int $id) {
        try {
            $stage = Stage::query()->findOrFail($id);
            $stage->delete();
            return response()->json([
                'success' => 'Xoá thành công'
            ]);
        }catch (\Exception $exception){
            return response()->json([
                'error' => 'Giai đoạn có chứa nhiệm vụ'
            ], 500);
        }
    }
}
