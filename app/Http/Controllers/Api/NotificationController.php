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
        if (isset($request->include)) {
            $countNotifications = Notification::where('account_id', Auth::id())
                ->where('new', true)
                ->count();

            return response()->json($countNotifications);
        }

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

    public function destroy(int $id, Request $request)
    {
        if ($request->filled('all')) {
            Notification::where('account_id', Auth::id())->delete();
        } else {
            Notification::find($id)->delete();
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

    public function numberNotification()
    {
        $countNotifications = Notification::where('account_id', Auth::id())
            ->where('new', true)
            ->count();

        return response()->json($countNotifications);
    }
}
