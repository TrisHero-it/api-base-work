<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class NoticeController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->filled('per_page') ? $request->per_page : 18;
        $notices = Notification::where('is_notice', true);
        if ($request->filled('include')) {
            $notices = $notices->where('is_hidden', true);
        } else {
            $notices = $notices->where('is_hidden', false);
        }

        if ($request->filled('search')) {
            $notices = $notices->where('title', 'like', '%' . $request->search . '%');
        }

        $count = Notification::where('is_notice', true)->where('is_hidden', false)->count();
        $count_hidden = Notification::where('is_notice', true)->where('is_hidden', true)->count();

        $notices = $notices->paginate($perPage);
        return response()->json([
            'current_page' => $notices->currentPage(),
            'data' => $notices->items(),
            'per_page' => $notices->perPage(),
            'last_page' => $notices->lastPage(),
            'from' => $notices->firstItem(),
            'to' => $notices->lastItem(),
            'total_pages' => $notices->lastPage(),
            'total' => $notices->total(),
            'count' => $count,
            'count_hidden' => $count_hidden
        ]);
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
        $data = $request->except('seen_by');

        if ($request->filled('action')) {
            if (!in_array(auth()->user()->id, $notice->seen_by)) {
                $data['seen_by'] = array_merge($notice->seen_by, [auth()->user()->id]);
            }
        }

        $notice->update($data);

        return response()->json($notice);
    }

    public function destroy($id)
    {
        $notice = Notification::find($id);
        $notice->delete();

        return response()->json($notice);
    }

    public function getNoticeToday()
    {
        $notice = Notification::where('is_notice', true)
            ->where('created_at', '>=', now()->startOfDay())
            ->where('created_at', '<=', now()->endOfDay())
            ->get();
    }
}
