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
    public function index()
    {
        $attendance = Attendance::all();

        return response()->json($attendance);
    }

    public function checkIn(Request $request)
    {
        $token = $request->header('Authorization');
        $token = explode(' ', $token)[1];
        $a = Account::query()->where('remember_token', $token)->first();
        if (isset($a)) {
            $account = Attendance::where('account_id', $a->account_id)->orderBy('id', 'desc')->first();
        }
        if (isset($account)) {
            $isToday = Carbon::parse($account->checkin)->isToday();
        }
        if (isset($isToday)) {
            return response()->json([
                'error' => 'Hôm nay bạn đã điểm danh rồi'
            ]);
        } else {
            Attendance::query()
                ->create([
                    'account_id' => 4,
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
        $account = Attendance::where('account_id', 4)->orderBy('id', 'desc')->first();
        $isToday = Carbon::parse($account->checkin)->isToday();
        if (!$isToday) {

            return response()->json([
                'error' => 'Bạn chưa điểm danh @@'
            ]);
        } else {
            if (isset($account->checkout)) {

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
