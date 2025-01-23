<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\DateHoliday;
use App\Models\Notification;
use App\Models\Propose;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProposeController extends Controller
{
    public function index(Request $request)
    {
        $proposes = Propose::query()->orderBy('created_at', 'desc')->with(['account', 'propose_category', 'date_holidays']);
        if (isset($request->status)) {
            $proposes = $proposes->where('status', $request->status);
        }

        if (isset($request->propose_category_id)) {
            $proposes = $proposes->where('propose_category_id', $request->propose_category_id);
        }

        if (isset($request->account_id)) {
            $proposes = $proposes->where('account_id', $request->account_id);
        }

        if (isset($request->date)) {
            $date = explode("-", $request->date);
            $year = $date[0];
            $month = $date[1];
            $proposes = $proposes->whereMonth('created_at', $month)->whereYear('created_at', $year);
        }

        $proposes = $proposes->get();
        foreach ($proposes as $propose) {
            $propose['date'] = $propose->date_holidays;
            $propose['account'] = $propose->account;
            $propose['avatar'] = $propose->account->avatar;
            $propose['category_name'] = $propose->propose_category_id == null ? 'Tuỳ chỉnh' : $propose->propose_category->name;;
            unset($propose['date_holidays']);
            unset($propose['propose_category']);
            unset($propose['account_id']);
        }

        return response()->json($proposes);
    }

    public function store(Request $request)
    {
        $a = Auth::user();
        $data = $request->except('holiday');
        $data['account_id'] = $a->id;
        $arr = [];
        $propose = Propose::query()->create($data);

        if (isset($request->holiday)) {
            foreach ($request->holiday as $date) {
                $a =  ['propose_id' => $propose->id];
                $arr[] = array_merge($a, $date);
            }
        }
        DateHoliday::query()->insert($arr);

        return response()->json($propose);
    }

    public function update(int $id, Request $request)
    {
        if (!Auth::user()->isSeniorAdmin()) {
            return response()->json([
                'message' => 'Bạn không có quyền thao tác',
                'errors' => 'Bạn không có quyền thao tác'
            ], status: 403);
        }
        $propose = Propose::query()->with('propose_category')->findOrFail($id);
        $propose->update($request->all());
        if ($request->status == 'approved' && $propose->propose_category->name == 'Sửa giờ vào ra') {
            $date = explode(' ', $propose->start_time)[0];
            $attendance = Attendance::whereDate('checkin', $date)->where('account_id', $propose->account_id)
                ->first();
            $attendance->update([
                'checkin' => $propose->start_time,
                'checkout' => $propose->end_time,
            ]);
        }
        $name = $propose->propose_category->name;
        $status = $propose->status == 'approved' ? 'được chấp nhận' : 'bị từ chối';
        Notification::create([
            'account_id' => $propose->account_id,
            'title' => "$name của bạn đã " . $status,
            'message' => "<strong>$name</strong> của bạn đã " . $status,
            'manager_id' => auth()->id()
        ]);

        return response()->json($propose);
    }

    public function destroy(int $id)
    {
        $propose = Propose::query()->findOrFail($id);
        $propose->delete();

        return response()->json(data: ['success' => 'Xoá thành công']);
    }
}
