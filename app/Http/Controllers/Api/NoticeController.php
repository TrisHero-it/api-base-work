<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class NoticeController extends Controller
{
    public function index(Request $request)
    {
        $notice = Notification::where('is_notice', true)->get();

        return response()->json($notice);
    }

    public function store(Request $request)
    {
        $notice = Notification::create($request->all());
        return response()->json($notice);
    }

    public function update(Request $request, $id)
    {
        $notice = Notification::find($id);
        $notice->update($request->all());
        return response()->json($notice);
    }

    public function destroy($id)
    {
        $notice = Notification::find($id);
        $notice->delete();
        return response()->json($notice);
    }
}
