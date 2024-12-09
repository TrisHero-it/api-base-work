<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StageStoreRequest;
use App\Http\Requests\StageUpdateRequest;
use App\Models\Stage;
use App\Models\StickerTask;
use App\Models\Task;
use Carbon\Carbon;
use http\Client\Response;
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
        foreach ($stages as $stage) {
           if ($stage->isSuccessStage() || $stage->isFailStage()) {
               $tasks = Task::query()->where('stage_id', $stage['id'])->orderBy('updated_at', 'desc')->whereBetween('updated_at', [$startOfLastWeek, $endOfThisWeek])->get();
               foreach ($tasks as $task) {
                   $stickers = StickerTask::query()->select('sticker_id')->where('task_id', $task->id)->get();
                   foreach ($stickers as $sticker) {
                       $sticker['name'] = $sticker->sticker->title;
                   }
                   $task['sticker'] = $stickers;
               }
           }else {
                $tasks = Task::query()->where('stage_id', $stage['id'])->orderBy('updated_at', 'desc')->get();
                foreach ($tasks as $task) {
                   $stickers = StickerTask::query()->select('sticker_id')->where('task_id', $task->id)->get();
                    foreach ($stickers as $sticker) {
                        $sticker['name'] = $sticker->sticker->title;
                    }
                    $task['sticker'] = $stickers;
                }
           }
            foreach ($tasks as $task) {
//   thay id bằng mã code của nhiệm vụ khi trả cho client
                $task['id'] = $task->code;
            }
//   Hiển thị danh sách nhiệm vụ của stages
            $stage['tasks'] = $tasks;
        }

        return response()->json($stages);
    }

    public function store(StageStoreRequest $request) {
            if (!isset($request->index)) {
//              Nếu như không truyeefn lên vị trí của stages thì sẽ thêm vào stages mới nht
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
