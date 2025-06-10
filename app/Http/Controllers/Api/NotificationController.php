<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
                ->where('is_notice', false)
                ->count();

            return response()->json($countNotifications);
        }

        $notifications = Notification::query()
            ->orderBy('id', 'desc')
            ->where('account_id', Auth::id())
            ->with('manager')
            ->where('is_notice', false)
            ->get();

        return response()->json($notifications);
    }

    public function store(Request $request)
    {
        $data = $request->except('new', 'seen');
        if ($request->hasFile('thumbnail')) {
            $file = $request->file('thumbnail');
            $path = $file->store('notifications', 'public');
            $data['thumbnail'] = $path;
        } else if ($request->filled('thumbnail')) {
            $data['thumbnail'] = $request->thumbnail;
        }
        $notification = Notification::create($data);

        return response()->json($notification);
    }

    public function update(int $id, Request $request)
    {
        $notification = Notification::find($id)
            ->update($request->all());

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

        Notification::where('account_id', Auth::id())
            ->where('seen', false)
            ->update([
                'seen' => true
            ]);

        return response()->json([
            'success' => 'Đã đọc thông báo'
        ]);
    }

    public function numberNotification()
    {
        $countNotifications = Notification::where('account_id', Auth::id())
            ->where('seen', false)
            ->count();

        return response()->json($countNotifications);
    }
}
