<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use IPTools\IP;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $a = explode(' ', $request->header('Authorization'));
        $attendance = Attendance::query();
        //  Loc theo ngày
        if (isset($request->start) && isset($request->end)) {
            $attendance->where('created_at', '>=', $request->start)->where('created_at', '<=', $request->end);
        }
        //  Lọc theo tháng
        if (isset($request->date)) {
            $date = explode('-', $request->date);
            $month = $date[1];
            $year = $date[0];
            $attendance->whereMonth('created_at', $month)->whereYear('created_at', $year);
        }

        if (!isset($request->start) && !isset($request->date)) {
            $attendance->whereMonth('created_at', date('m'));
        }

        $attendance = $attendance->get();

        foreach ($attendance as $value) {

            $dateTime = new \DateTime($value->checkin);
            $nineAM = clone $dateTime;
            $nineAM->setTime(9, 1, 0);
            if ($dateTime > $nineAM) {
                $value['onTime'] = False;
            }else {
                $value['onTime'] = True;
            }
        }

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

    public function newCheckIn(Request $request)
    {
        $userIp = $request->ip(); // Lấy địa chỉ IP của người dùng
        $allowedIpRange = '192.168.1.0/24'; // Dải IP của mạng LAN nhà bạn

        return $userIp;

    }
}
