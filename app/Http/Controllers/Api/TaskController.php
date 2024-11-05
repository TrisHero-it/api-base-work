<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Stage;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TaskController extends Controller
{
    public function index($id)
    {
        $tasks = Task::query()->where('stage_id', $id)->orderBy('updated_at', 'desc')->get();
        return response()->json($tasks);
    }

    public function store(Request $request)
    {
        try {
            $account = Account::query()->where('id', $request->account_id)->first() ?? null;
            $stage = Stage::query()->where('workflow_id', $request->workflow_id)->orderByDesc('id')->first();
            $task = Task::query()->create([
                'code' => rand(100000, 999999),
                'name' => $request->name,
                'description' => $request->description ?? null,
                'account_id' => $account->id ?? null,
                'stage_id' => $stage->id,
                'expired'=> $request->expired ?? null
            ]);

            return response()->json([
                'success' => 'Thêm thành công'
            ]);
        }catch (\Exception $exception){
            return response()->json([
                'error' => 'Đã xảy ra lỗi'
            ]);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $task = Task::query()->findOrFail($id);
            $stage = Stage::query()->where('id', $request->stage_id)->first();
            $data = $request->all();
            if ($stage->index==0) {
                $data['failed_at'] = $task->stage->name;
            }else {
                $data['failed_at'] = null;
                $data['reason'] = null;
            }
            if ($stage->name=='Hoàn thành'){
                if ($task->account_id != null) {
                    $task->update($data);
                   }else {
                    return response()->json([
                        'error' => 'Chưa có người nhận nhiệm vụ'
                    ]);
                }
            }
            else if($stage->name=='Thất bại') {
                if ($task->account_id != null) {
                    $task->update($data);
                    return response()->json([
                        'success' => 'Chỉnh sửa thành công'
                    ]);
                }else {
                    return response()->json([
                        'error' => 'Chưa có người nhận nhiệm vụ'
                    ]);
                }
            }
            else {
                $task->update(
                    $data
                );
                return response()->json([
                    'success' => 'Chỉnh sửa thành công'
                ]);
            }

        }catch ( \Exception $exception){
            return response()->json([
                'error' => 'Đã xảy ra lỗi : '.$exception->getMessage()
            ]);
        }

    }

    public function show(int $id) {
        $task = Task::query()->findOrFail($id);

        return response()->json($task);
    }

    public function destroy($id)
    {
        try {
            $task = Task::query()->findOrFail($id);
            $task->delete();
            return response()->json([
                'success' => 'Xoá thành công'
            ]);
        }catch (\Exception $exception){
            return response()->json([
                'error' => 'Đã xảy ra lỗi : '.$exception->getMessage()
            ]);
        }
    }

    public function uploadImage(Request $request)
    {
        try {
            $image = $request->file('image');
            $imageUrl = Storage::put('/public/images', $image);
            $imageUrl = Storage::url($imageUrl);
            return response()->json(['urlImage'=> $imageUrl]);
        }catch (\Exception $exception){
            return response()->json(['error', 'Lỗi'], 500);
        }

    }

}
