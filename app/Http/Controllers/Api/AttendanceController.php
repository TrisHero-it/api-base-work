<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AccountDepartment;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\Propose;
use App\Models\ProposeCategory;
use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
                $attendance->whereMonth('created_at', $month)
                    ->whereYear('created_at', $year);
            }
            if (!Auth::user()->isSeniorAdmin()) {
                $attendance->where('account_id', Auth::id());
            }
            if (!isset($request->start) && !isset($request->date)) {
                $attendance->whereMonth('created_at', date('m'));
            }

            $attendance = $attendance->get();
            foreach ($attendance as $item) {
                $item['check_out_regulation'] = Carbon::parse($item->checkin)
                    ->addHours(9)
                    ->format('Y-m-d H:i:s');
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
            // số ngày nghỉ có phép của tài khoản
            $accountDayOff = Auth::user()->day_off;
            $a = $dayoff - $accountDayOff;
            // nếu như số ngày nghỉ vẫn trong khoảng thời gian cho phép
            if ($dayoff < $accountDayOff) {
                $data['day_off_with_pay'] = $dayoff;
                $data['day_off_without_pay'] = 0;
                $data['day_off_account'] = $accountDayOff - $dayoff;
            } else {
                $data['day_off_with_pay'] = $accountDayOff;
                $data['day_off_without_pay'] = $a;
                $data['day_off_account'] = 0;
            }
            $data['total_over_time'] = $timeOverTime;
        }

        return response()->json($data);
    }

    public function checkIn(Request $request)
    {
        $currentTime = Carbon::now();
        $startTime = Carbon::createFromTime(12, 0, 0); // Thời gian bắt đầu: 12:00
        $endTime = Carbon::createFromTime(13, 30, 0);  // Thời gian kết thúc: 13:30
        $attendance = Attendance::where('account_id', Auth::id())->latest('id')->first();
        $arrId = [];
        $department = Department::where('name', 'Phòng sales')->first()->id;
        $accountDepartments = AccountDepartment::where('department_id', $department)->get();
        foreach ($accountDepartments as $accountDepartment) {
            $arrId[] = $accountDepartment->account_id;
        }
        $saleMembers = Account::whereIn('id', $arrId)->get()->toArray();
        $arrAccountId = [];
        foreach ($saleMembers as $saleMember) {
            $arrAccountId[] = $saleMember['id'];
        }
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
                                'error' => 'Hôm nay bạn đã điểm danh rồi'
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
                    'error' => 'Không được điểm danh vào thời gian này'
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
