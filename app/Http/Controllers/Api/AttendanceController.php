<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Attendance;
use App\Models\Propose;
use App\Models\ProposeCategory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use IPTools\IP;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $attendance = Attendance::query();
        //  Loc theo ngày
        if (isset($request->start) && isset($request->end)) {
            $attendance->where('created_at', '>=', $request->start)
                       ->where('created_at', '<=', $request->end);
        }
        //  Lọc theo tháng
        if (isset($request->date)) {
            $date = explode('-', $request->date);
            $month = $date[1];
            $year = $date[0];
            $attendance->whereMonth('created_at', $month)
                       ->whereYear('created_at', $year);
        }
        if (!Auth::user()->isSeniorAdmin()) {
            $attendance->where('account_id', Auth::id());
        }
        if (!isset($request->start) && !isset($request->date)) {
            $attendance->whereMonth('created_at', date('m'));
        }
        $idOverTime = ProposeCategory::query()->where('name', 'Đăng ký OT')->first();
        if(isset($idOverTime)) {
            $idOverTime = $idOverTime->id;
        }
        $attendance = $attendance->get();
        foreach ($attendance as $value) {
            $value['start_over_time'] = null;
            $value['end_over_time'] = null;
            $dateTime = new \DateTime($value->checkin);
            $b = $dateTime->modify("+9 hours 1 minutes");
            $value['estimated_checkout'] = $b->format('Y-m-d H:i:s');
            $aa = explode(' ', $value->checkin);
            if (isset($idOverTime)) {
                $overTime = Propose::query()
                ->where('propose_category_id', $idOverTime)
                ->whereDate('start_time',$aa[0])
                ->first();
            if (isset($overTime)) {
                $value['start_over_time'] = $overTime->start_time;
                $value['end_over_time'] = $overTime->end_time;
            } 
            }
        }
        if (isset($request->me)) {
            $account = Auth::user();
            $attendance = Attendance::query()
            ->where('account_id', $account->id)
            ->whereDate('checkin', Carbon::today())
            ->orderBy('id')
            ->first();
        }

        return response()->json($attendance);
    }

    public function  checkIn(Request $request)
    {
        $isToday = false;
        if (Auth::check()) {
            $account = Attendance::where('account_id', Auth::id())->orderBy('id', 'desc')->first();
        }
        if (isset($account)) {
            if ($account->checkin != null) {
                $isToday = Carbon::parse($account->checkin)->isToday();
            }
        }

       if ($isToday == true) {
           return response()->json([
               'error' => 'Hôm nay bạn đã điểm danh rồi'
           ]);
       } else {
            Attendance::query()
                ->create([
                    'account_id' => Auth::id(),
                    'checkin' => now()
                ]);

            return response()
                ->json([
                    'success' => 'Đã điểm danh'
                ]);
       }
    }

    public function checkOut(Request $request)
    {
        $isToday = false;
        $account = Attendance::where('account_id', Auth::id())->orderBy('id', 'desc')->first();
        $isToday = Carbon::parse($account->checkin)->isToday();
        if ($isToday == false) {
            return response()->json([
                'error' => 'Bạn chưa điểm danh @@'
            ]);
        } else {
            if ($account->checkout != null) {

                return response()->json([
                    'error' => 'Hôm nay bạn đã checkout rồi'
                ]);
            } else {
                $account->update([
                    'checkout' => now()
                ]);

                return response()->json([
                    'success' => 'checkout thành công'
                ]);
            }
        }
    }
}
