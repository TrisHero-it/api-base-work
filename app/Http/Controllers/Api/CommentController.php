<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CommentStoreRequest;
use App\Http\Requests\CommentUpdateRequest;
use App\Models\Account;
use App\Models\AccountProfile;
use App\Models\Comment;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    public function index(Request $request)
    {
        $task = Task::query()->where('code', $request->task_id)->first();

        $comments = Comment::query()->where('task_id', $task->id)->where('comment_id', null)->orderByDesc('id')->get();
        foreach ($comments as $comment) {
            $account = Account::query()->where('id', $comment->account_id)->first();

            $comment['avatar'] = $account->avatar;
            $comment['full_name'] = $account->full_name;
            $comment['task_id'] = $request->task_id;
            $replies = Comment::query()->where('comment_id', $comment->id)->orderByDesc('id')->get();
            foreach ($replies as $reply) {
                $account2 = Account::query()->where('id', $reply->account_id)->first();
                $reply['avatar'] = $account2->avatar;
                $reply['task_id'] = $request->task_id;
                $reply['full_name'] = $account2->full_name;
            }
            $comment['children'] = $replies;
        }

        return response()->json($comments);
    }

    public function store(CommentStoreRequest $request)
    {
            $data = $request->except('task_id', 'account_id');
            $convertedText = $this->convertLinksToAnchors($data['content']);
            $data['content'] = $convertedText;
            $data['account_id'] = Auth::id();
            $task = Task::query()->where('code', $request->task_id)->first();
            $data['task_id'] = $task->id;
            $comment = Comment::query()->create($data);

            return response()->json($comment);
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

    public function update(CommentUpdateRequest $request, int $id)
    {
            $comment = Comment::query()->findOrFail($id);
            $comment->update($request->all());
            return response()->json($comment);
    }

    public function convertLinksToAnchors($text)
    {
        // Biểu thức chính quy tìm URL
        $pattern = '/(https?:\/\/[^\s]+)/i';

        // Thay thế URL bằng thẻ <a>
        $replacement = '<a href="$1" target="_blank">$1</a>';

        // Trả về chuỗi đã thay đổi
        return preg_replace($pattern, $replacement, $text);
    }
}
