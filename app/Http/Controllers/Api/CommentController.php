<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Task;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function index(int $id)
    {
        $task = Task::query()->where('code', $id)->first();

        $comments = Comment::query()->where('task_id', $task->id)->where('comment_id', null)->get();
        foreach ($comments as $comment) {
            $comment['children'] = Comment::query()->where('comment_id', $comment->id)->get();
        }

        return response()->json($comments);
    }

    public function store(Request $request)
    {
        try {
            $data = $request->except('task_id');
            $task = Task::query()->where('code', $request->task_id)->first();
            $data['task_id'] = $task->id;
            $comment = Comment::query()->create($data);
            return response()->json([
                'success' => 'Thêm thành công'
            ]);
        }catch (\Exception $exception){
            return response()->json([
                'error' => 'Đã xảy ra lỗi'
            ]);
        }
    }

    public function destroy(int $id)
    {
        try {
            $comment = Comment::query()->findOrFail($id);
            $comment->delete();
            return response()->json([
                'success' => 'Xoá thành công'
            ]);
        }catch (\Exception $exception){
            return response()->json([
                'error' => 'Đã xảy ra lỗi'
            ], 500);
        }
    }

    public function update(Request $request, int $id)
    {
        try {
            $comment = Comment::query()->findOrFail($id);
            $comment->update($request->all());
            return response()->json([
                'success' => 'Chỉnh sửa thành công'
            ]);
        }catch (\Exception $exception){
            return response()->json([
                'error' => 'Đã xảy ra lỗi'
            ], 500);
        }
    }
}
