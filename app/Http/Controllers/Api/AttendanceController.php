<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $a = explode(' ', $request->header('Authorization'));
        $token = $a[1];
        $attendance = Attendance::all();
        if (isset($request->me)) {
            $account = Account::query()->where('remember_token', $a[1])->first();
            $attendance = Attendance::query()->where('account_id', $account->id)->whereDate('checkin', Carbon::today())->orderBy('id')->first();
        }


        return response()->json($attendance);
    }

    public function checkIn(Request $request)
    {
        $isToday = false;
        $token = $request->header('Authorization');
        $token = explode(' ', $token)[1];
        $a = Account::query()->where('remember_token', $token)->first();
        if (isset($a)) {
            $account = Attendance::where('account_id', $a->id)->orderBy('id', 'desc')->first();
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
                    'account_id' => $a->id,
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
        $token = $request->header('Authorization');
        $token = explode(' ', $token)[1];
        $a = Account::query()->where('remember_token', $token)->first();
        $account = Attendance::where('account_id', $a->id)->orderBy('id', 'desc')->first();
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
