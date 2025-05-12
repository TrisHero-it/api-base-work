<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class NoticeController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->filled('per_page') ?? 10;
        $notices = Notification::where('is_notice', true);
        if ($request->filled('include')) {
            $notices = $notices->where('is_hidden', true);
        }else {
        $notices = $notices->where('is_hidden', false);
        }

        $count = $notices->count();
        $notices = $notices->paginate($perPage);

        return response()->json($notices);
    }

    public function store(Request $request)
    {
        $data = $request->all();
        $data['manager_id'] = auth()->user()->id;
        $data['is_notice'] = true;
        $notice = Notification::create($data);

        return response()->json($notice);
    }

    public function show($id)
    {
        $notice = Notification::where('is_notice', true)
        ->with(['account', 'manager'])
        ->findOrFail($id);

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
