<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CommentStoreRequest;
use App\Http\Requests\CommentUpdateRequest;
use App\Models\Account;
use App\Models\Comment;
use App\Models\Notification;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    public function index(Request $request)
    {
        $task = Task::query()->findOrFail($request->task_id);

        $comments = Comment::query()
            ->where('task_id', $task->id)
            ->where('comment_id', null)
            ->orderByDesc('id')
            ->get();
        foreach ($comments as $comment) {
            $account = Account::query()
                ->where('id', $comment->account_id)
                ->first();

            $comment['avatar'] = $account->avatar;
            $comment['full_name'] = $account->full_name;
            $comment['task_id'] = $request->task_id;
            $replies = Comment::query()
                ->where('comment_id', $comment->id)
                ->orderByDesc('id')
                ->get();
            foreach ($replies as $reply) {
                $account2 = Account::query()
                    ->where('id', $reply->account_id)
                    ->first();
                $reply['avatar'] = $account2->avatar;
                $reply['task_id'] = $request->task_id;
                $reply['full_name'] = $account2->full_name;
            }
            $comment['children'] = $replies;
        }

        return response()->json($comments);
    }

    public function store(Request $request)
    {
        $data = $request->except('task_id', 'account_id', 'tags', 'link');
        $data['account_id'] = Auth::id();
        $data['task_id'] = $request->task_id;
        $comment = Comment::query()->create($data);
        $data2 = [];
        if (isset($request->tags)) {
            foreach ($request->tags as $a) {
                $data2[] = [
                    'title' => Auth::user()->full_name . ' đã nhắc đến bạn',
                    'message' => Auth::user()->full_name . ' đã nhắc đến bạn',
                    'link' => env('APP_URL') . "/task" . "/$request->task_id",
                    'account_id' => $a,
                    'manager_id' => Auth::id(),
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }
            Notification::insert($data2);
        }

        return response()->json($comment);
    }

    public function destroy(int $id)
    {
        try {
            $comment = Comment::query()
                ->findOrFail($id);
            $comment->delete();

            return response()->json([
                'success' => 'Xoá thành công'
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'error' => 'Đã xảy ra lỗi'
            ], 500);
        }
    }

    public function update(CommentUpdateRequest $request, int $id)
    {
        $comment = Comment::query()
            ->findOrFail($id);
        $comment->update($request->all());

        return response()->json($comment);
    }

    public function notification(Request $request)
    {
        $accounts = Account::query()
            ->whereIn('id', $request->account_id)
            ->get();
        foreach ($accounts as $account) {
            Notification::create([
                'title' => Auth::user()->full_name . ' đã nhắc đến bạn',
                'message' => $request->message,
                'link' => $request->link,
                'account_id' => $account->id,
                'manager_id' => Auth::id(),
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        return response()->json([
            'success' => 'Thông báo thành công'
        ]);
    }
}
