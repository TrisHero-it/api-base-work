<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = Notification::query()
            ->orderBy('id', 'desc')
            ->where('account_id', Auth::id())
            ->get();

        return response()->json($notifications);
    }

    public function update(int $id, Request $request)
    {
        $notification = Notification::find($id)->update($request->all());

        return response()->json($notification);
    }

    public function delete(int $id)
    {
        $notification = Notification::find($id)->delete();

        return response()->json([
            'success' => 'Xoá thành công'
        ]);
    }
}
