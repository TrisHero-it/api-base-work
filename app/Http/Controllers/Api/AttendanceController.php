<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AccountDepartment;
use App\Models\Attendance;
use App\Models\DateHoliday;
use App\Models\Department;
use App\Models\ipWifi;
use App\Models\Propose;
use App\Models\ProposeCategory;

use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Jenssegers\Agent\Agent;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $month = Carbon::now()->month;
        $year = Carbon::now()->year;
        $startMonth = Carbon::now()->startOfMonth();
        $now = Carbon::now();
        if (isset($request->me)) {
            $account = Auth::user();
            $attendance = Attendance::query()
                ->where('account_id', $account->id)
                ->whereDate('checkin', Carbon::today())
                ->orderBy('id')
                ->get();
            $data = $attendance;
        } else {
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
            }
            $attendance->whereMonth('checkin', $month)
                ->whereYear('checkin', $year);
            if (!Auth::user()->isSeniorAdmin()) {
                $attendance->where('account_id', Auth::id());
            }
            if (!isset($request->start) && !isset($request->date)) {
                $attendance->whereMonth('created_at', date('m'));
            }
            $attendance = $attendance->get();


            foreach ($attendance as $item) {
                $hours = 0;
                $item['check_out_regulation'] = Carbon::parse($item->checkin)
                    ->addHours(9)
                    ->format('Y-m-d H:i:s');

                $checkin = Carbon::parse($item->checkin);
                $checkout = Carbon::parse($item->checkout);
                $noonTime = $checkin->copy()->setHour(12)->setMinute(0)->setSecond(0);
                if ($item->checkout != null) {
                    if (!Auth::user()->isSalesMember()) {
                        if ($checkin->greaterThanOrEqualTo($noonTime)) {
                            $hours = $checkout->floatDiffInHours($checkin);
                        } else {
                            $hours = $checkout->floatDiffInHours($checkin) - 1.5;
                        }
                    } else {
                        $hours = $checkout->floatDiffInHours($checkin);
                    }
                }

                $item['hours'] = number_format($hours, 2);
            }

            $data = [];
            $data['attendances'] = $attendance;
            $data['standard_work'] = Schedule::whereMonth('day_of_week', $month)
                ->whereYear('day_of_week', $year)
                ->where('go_to_work', 1)
                ->get()
                ->count();
            $numberWorkingDays = 0;
            foreach ($attendance as $item) {
                if ($item->checkout != null) {
                    $checkOut = Carbon::parse($item->checkout);
                    $checkIn = Carbon::parse($item->checkin);
                    $diff = $checkOut->diff($checkIn);
                    $totalHours = $diff->days * 24 + $diff->h + ($diff->i / 60);
                    $numberWorkingDays += round($totalHours / 9, 2);
                }
            }
            if (Auth::user()->isSeniorAdmin()) {
                $data['number_of_working_days'] = 0;
            } else {
                $data['number_of_working_days'] = $numberWorkingDays;
            }
            $dayoff = 0;
            for ($date = $startMonth; $date->lte($now); $date->addDay()) {
                $date2 = $date->format('Y-m-d');
                $schedule = null;
                // Đây là ngày đi làm
                $schedule = Schedule::whereDate('day_of_week', $date2)
                    ->where('go_to_work', true)
                    ->first();
                if (isset($schedule) && $schedule != null) {
                    $atten = null;
                    // nếu như là ngày đi làm thì check xem hôm í ông này có điểm danh hay không
                    $atten = Attendance::whereDate('checkin', $date2)
                        ->where('account_id', Auth::id())
                        ->first();
                    // Nếu như không điểm danh thì tính là 1 hôm nghỉ không phép
                    if (!(isset($atten) && $atten != null)) {
                        $dayoff++;
                    }
                }
            }
            $accountDayOff = Auth::user()->day_off;
            // số ngày nghỉ có phép của tài khoản
            $proposes = Propose::whereIn('name', ['Nghỉ có hưởng lương', 'Đăng ký OT'])
                ->where('account_id', Auth::id())
                ->where('status', 'approved')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->get();
            $dayOffWithPay = 0;
            $idProposeHoliday = $proposes->where('name', 'Nghỉ có hưởng lương')->pluck('id');
            $holidays = DateHoliday::whereIn('propose_id', $idProposeHoliday)->get();
            foreach ($holidays as $holiday) {
                $dayOffWithPay += $holiday->number_of_days;
            }
            $overTime = $proposes->where('name', 'Đăng ký OT')->count();
            $data['total_over_time'] = number_format($overTime, 2);
            $data['day_off_with_pay'] = number_format($dayOffWithPay, 2);
            $data['day_off_account'] = number_format($accountDayOff, 2);
            $data['day_off_without_pay'] = number_format($dayoff - $dayOffWithPay, 2);
        }

        return response()->json($data);
    }

    public function checkIn(Request $request)
    {
        // $ipWifi = ipWifi::where('ip', $request->ip_wifi)->first();
        // if ($ipWifi == null) {
        //     if (Auth::user()->attendance_at_home == false) {
        //         return response()->json([
        //             'message' => 'ip không được cho phép',
        //             'errors' => [
        //                 'ip_wifi' => 'ip không được cho phép'
        //             ]
        //         ], 401);
        //     }
        // }
        $currentTime = Carbon::now();
        $startTime = Carbon::createFromTime(12, 0, 0); // Thời gian bắt đầu: 12:00
        $endTime = Carbon::createFromTime(13, 30, 0);  // Thời gian kết thúc: 13:30
        $attendance = Attendance::where('account_id', Auth::id())->latest('id')->first();
        $arrId = [];
        $department = Department::where('name', 'Phòng sales')->first()->id;
        $accountDepartments = AccountDepartment::where('department_id', $department)->get();
        $arrId = $accountDepartments->pluck('account_id')->toArray();
        $saleMembers = Account::whereIn('id', $arrId)->get();
        $arrAccountId = $saleMembers->pluck('id')->toArray();
        // check xem có trong khoảng giờ nghỉ trưa hay không
        if (!$currentTime->between($startTime, $endTime) || in_array(Auth::id(), $arrAccountId)) {
            // nếu tài khoản là tài khoản trong phòng sales hoặc chưa điểm danh trong hnay thì mới được điểm danh
            if (in_array(Auth::id(), $arrAccountId)) {
                Attendance::query()
                    ->create([
                        'account_id' => Auth::id(),
                        'checkin' => now()
                    ]);
                return response()
                    ->json([
                        'success' => 'Đã điểm danh'
                    ]);
            } else {
                if ($attendance != null) {
                    if ((Carbon::parse($attendance->checkin)->isToday())) {
                        return response()
                            ->json([
                                'message' => 'Hôm nay bạn đã điểm danh rồi'
                            ], 403);
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
        } else {
            return response()
                ->json([
                    'message' => 'Không được điểm danh vào thời gian này'
                ], 403);
        }
    }

    public function show($id)
    {
        $attendance = Attendance::findOrFail($id);

        return response()->json([$attendance]);
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
