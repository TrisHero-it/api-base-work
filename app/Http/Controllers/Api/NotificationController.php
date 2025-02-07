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
            ->with('manager')
            ->get();

        return response()->json($notifications);
    }

    public function update(int $id, Request $request)
    {
        $notification = Notification::find($id)->update($request->all());

        return response()->json($notification);
    }

    public function delete(int $id, Request $request)
    {
        if (isset($request->all)) {
            Notification::where('account_id', Auth::id())->delete();
        } else {
            $notification = Notification::find($id)->delete();
        } 

        return response()->json([
            'success' => 'Xoá thành công'
        ]);
    }

    public function seenNotification(Request $request)
    {
        $notifications = Notification::where('account_id', Auth::id())
        ->where('new', true)
        ->update([
            'new' => false
        ]);

        return response()->json($notifications);
    }
}
