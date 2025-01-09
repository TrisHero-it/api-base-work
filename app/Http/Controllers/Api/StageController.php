<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StageStoreRequest;
use App\Http\Requests\StageUpdateRequest;
use App\Models\Stage;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Http\Request;

class StageController extends Controller
{
    public function index(Request $request)
    {
        // Tuần này
        $endOfThisWeek = Carbon::now()->endOfWeek()->toDateString();
        // Tuần trước
        $startOfLastWeek = Carbon::now()->subWeek()->startOfWeek()->toDateString();

        $stages = Stage::query()->where('workflow_id', $request->workflow_id)->orderBy('index', 'desc')->get();
        $arrStageId = $stages->pluck('id');

        $tasks2 =  Task::query()->whereIn('stage_id', $arrStageId)->with('tags')->latest('updated_at')->get();
        foreach ($stages as $stage) {
           if ($stage->isSuccessStage()) {
               $tasks = $tasks2->where('stage_id', $stage->id)->whereBetween('completed_at', [$startOfLastWeek, $endOfThisWeek])->sortByDesc('completed_at');
           }else {
               $tasks = $tasks2->where('stage_id', $stage->id);
           }
        //Hiển thị danh sách nhiệm vụ của stages
            $tasks = array_values($tasks->toArray());
            $stage['tasks'] = $tasks;
        }

        return response()->json($stages);
    }

    public function store(StageStoreRequest $request) {
            if (!isset($request->index)) {
            //Nếu như không truyeefn lên vị trí của stages thì sẽ thêm vào stages mới nht
                $stage = Stage::query()->where('workflow_id', $request->workflow_id)->orderByDesc('index')->first();
                $stages = Stage::query()->create([
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
                    $stages = Stage::create([
                        'name' => $request->name,
                        'workflow_id'=> $request->workflow_id,
                        'description' => $request->description,
                        'index'=> $request->index,
                        'expired_after_hours'=> $request->expired_after_hours ?? null
                    ]);
                }else {
                    $stages = Stage::query()
                        ->where('workflow_id', $request->workflow_id)
                        ->where('index', '>', $request->index)
                        ->get();
                    foreach ($stages as $stage) {
                        $stage->update([
                            'index' => $stage->index+1
                        ]);
                    }
                    $stages =  Stage::create([
                        'name' => $request->name,
                        'workflow_id'=> $request->workflow_id,
                        'description' => $request->description,
                        'index'=> $request->index+1,
                        'expired_after_hours'=> $request->expired_after_hours ?? null
                    ]);
                }
            }

            return response()->json($stages);
    }

    public function update(int $id, StageUpdateRequest $request) {
            $stage = Stage::query()->findOrFail($id);
            $stage->update($request->validated());

            return \response()->json($stage);
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
