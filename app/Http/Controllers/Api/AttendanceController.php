<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AccountDepartment;
use App\Models\Attendance;
use App\Models\DateHoliday;
use App\Models\Department;
use App\Models\Propose;
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
        $numberWorkingDays = 0;

        if (isset($request->me)) {
            $account = Auth::user();
            $attendance = Attendance::query()
                ->where('account_id', $account->id)
                ->whereDate('checkin', Carbon::today())
                ->orderBy('checkin')
                ->get();
            $data = $attendance;
        } else {
            $attendance = Attendance::query();
            //  Loc theo ngày
            if (isset($request->start) && isset($request->end)) {
                $attendance->where('created_at', '>=', $request->start)
                    ->where('created_at', '<=', $request->end);
            }

            if (isset($request->account_id)) {
                $attendance->where('account_id', $request->account_id);
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
            $isSalesMember = Auth::user()->isSalesMember();

            $proposes = Propose::with(['date_holidays', 'propose_category'])->whereIn('name', ['Nghỉ có hưởng lương', 'Đăng ký OT', 'Nghỉ không hưởng lương', 'Đăng ký làm ở nhà', 'Đăng ký WFH'])
                ->select('id', 'account_id', 'name', 'propose_category_id', 'date_wfh')
                ->where('status', 'approved')
                ->whereMonth('created_at', $month ?? now()->month)
                ->whereYear('created_at', $year ?? now()->year);

            if ($request->filled('account_id')) {
                $proposes->where('account_id', $request->account_id);
            } else {
                if (!Auth::user()->isSeniorAdmin()) {
                    $proposes->where('account_id', Auth::id());
                }
            }

            $proposes = $proposes->get();
            foreach ($attendance as $item) {
                $hours = 0;
                $workday = 0;
                $item['check_out_regulation'] = Carbon::parse($item->checkin)
                    ->addHours(9)
                    ->format('Y-m-d H:i:s');

                $checkin = Carbon::parse($item->checkin);
                $checkout = Carbon::parse($item->checkout);
                $noonTime = $checkin->copy()->setHour(value: 13)->setMinute(30)->setSecond(0);
                if ($item->checkout != null) {
                    if (!$isSalesMember) {
                        if ($checkin->greaterThan($noonTime) || !$checkout->greaterThan($noonTime)) {
                            $hours = $checkout->floatDiffInHours($checkin);
                        } else {
                            $hours = $checkout->floatDiffInHours($checkin) - 1.5;
                        }
                    } else {
                        $hours = $checkout->floatDiffInHours($checkin);
                    }   
                }

                $item['hours'] = number_format($hours, 2);
                $workday = number_format($hours, 2) / 7.5;
                if ($workday > 1) {
                    $workday = 1;
                }
                if ($item->account_id == Auth::id()) {
                    $numberWorkingDays += $workday;
                }
                $item['workday'] = number_format($workday, 2);
            }
            $data = [];
            $arrDateHoliday = [];
            foreach ($proposes as $propose) {
                foreach ($propose->date_holidays as $holiday) {
                    $holiday['account_id'] = $propose->account_id;
                    $holiday['name'] = $propose->name;
                    $holiday['name_category'] = $propose->propose_category->name;
                    $arrDateHoliday[] = $holiday;
                }

                if ($propose->name == 'Đăng ký làm ở nhà') {
                    $a = [];
                    $a = array_merge($a, $propose->toArray());
                    $a['start_date'] = Carbon::parse($propose->date_wfh . ' 08:30:00');
                    $a['end_date'] = Carbon::parse($propose->date_wfh . ' 17:30:00');
                    unset($a['propose_category']);
                    unset($a['date_holidays']);
                    $arrDateHoliday[] = $a;
                }

                if ($propose->name == 'Đăng ký WFH') {
                    $a = [];
                    $a = array_merge($a, $propose->toArray());
                    $a['start_date'] = Carbon::parse($propose->date_wfh . ' 08:30:00')->format('Y-m-d H:i:s');
                    $a['end_date'] = Carbon::parse($propose->date_wfh . ' 17:30:00')->format('Y-m-d H:i:s');
                    unset($a['propose_category']);
                    unset($a['date_holidays']);
                    $arrDateHoliday[] = $a;
                    if ($propose->account_id == Auth::id()) {
                        $numberWorkingDays += 0.8;
                    }
                }
            }
            $data['ot_and_holiday'] = $arrDateHoliday;
            $data['attendances'] = $attendance;
            $data['standard_work'] = Schedule::whereMonth('day_of_week', $month)
                ->whereYear('day_of_week', $year)
                ->where('go_to_work', 1)
                ->get()
                ->count();
            $data['number_of_working_days'] = number_format($numberWorkingDays, 2);
            $accountDayOff = Auth::user()->day_off;
            // số ngày nghỉ có phép của tài khoản

            $dayOffWithPay = 0;
            $dayOffWithoutPay = 0;
            $idProposeHoliday = $proposes->where('name', 'Nghỉ có hưởng lương')->pluck('id');
            $holidays = DateHoliday::whereIn('propose_id', $idProposeHoliday)->get();
            // lấy ra ngày nghỉ không hưởng lương
            $idProposeDayOff = $proposes->where('name', 'Nghỉ không hưởng lương')->pluck('id');
            $dayOffWithoutPays = DateHoliday::whereIn('propose_id', $idProposeDayOff)->get();
            foreach ($holidays as $holiday) {
                $dayOffWithPay += $holiday->number_of_days;
            }
            foreach ($dayOffWithoutPays as $dayOff) {
                $dayOffWithoutPay += $dayOff->number_of_days;
            }
            $overTime = $proposes->where('name', 'Đăng ký OT')->count();
            $data['total_over_time'] = number_format($overTime, 2);
            $data['day_off_with_pay'] = number_format($dayOffWithPay, 2);
            $data['day_off_account'] = number_format($accountDayOff, 2);
            $data['day_off_without_pay'] = number_format($dayOffWithoutPay, 2);
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
        $department = Department::where('name', 'Phòng Sale')->first()->id;
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
                                'message' => 'Hôm nay bạn đã điểm danh rồi',
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
                    'message' => 'Không được điểm danh vào thời gian này',
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
        // if (Auth::user()->workAtHome()) {
        $isToday = false;
        $account = Attendance::where('account_id', Auth::id())->orderBy('checkin', 'desc')->first();
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
        // } else {
        //     return response()->json([
        //         'error' => 'Vui lòng checkout bằng máy chấm công'
        //     ]);
        // }
    }

    public function getHoursWork($task, $date)
    {
        $hoursWork = 0;
        if (Carbon::parse($task->started_at)->format('Y-m-d') == $date->format('Y-m-d')) {
            $start = Carbon::parse($task->started_at);
        } else {
            $start = Carbon::parse($date->format("Y-m-d") . " 08:30:00");
        }
        if ($start->format('Y-m-d') == now()->format('Y-m-d')) {
            $end = now();
        } else {
            $end = Carbon::parse($start)->setTime(17, 30);
        }
        $innerStart1 = Carbon::parse($start->format("Y-m-d") . " 08:30:00");
        $innerEnd1 = Carbon::parse($start->format("Y-m-d") . " 12:00:00");
        $innerStart2 = Carbon::parse($start->format("Y-m-d") . " 13:30:00");
        $innerEnd2 = Carbon::parse($start->format("Y-m-d") . " 17:30:00");
        if ($innerStart1->greaterThanOrEqualTo($start) && $innerEnd1->lessThanOrEqualTo($end)) {
            $hoursWork = $hoursWork + number_format(3.5, 3);
        } else {
            $validStart = max($innerStart1, $start);
            $validEnd = min($innerEnd1, $end);
            if ($validStart->lessThan($validEnd)) {
                $validHours = $validStart->floatDiffInHours($validEnd, true);
                $hoursWork += number_format($validHours, 3);
            }
        }
        if ($innerStart2->greaterThanOrEqualTo($start) && $innerEnd2->lessThanOrEqualTo($end)) {
            $hoursWork = $hoursWork + number_format(4, 3);
        } else {
            $validStart = max($innerStart2, $start);
            $validEnd = min($innerEnd2, $end);
            if ($validStart->lessThan($validEnd)) {
                $validHours = $validStart->floatDiffInHours($validEnd, true);
                $hoursWork += number_format($validHours, 3);
            }
        }

        return ['hours_work' => number_format($hoursWork, 2), 'start' => $start, 'end' => $end];
    }

    const ARRAY_ID_RONALJACK = [
        [
            "machine_id" => 1,
            'account_id' => 11,
            'name' => 'Long'
        ],
        [
            "machine_id" => 2,
            'account_id' => 14,
            'name' => 'Nghia'
        ],
        [
            "machine_id" => 3,
            'account_id' => 13,
            'name' => 'Manh'
        ],
        [
            "machine_id" => 6,
            'account_id' => 5,
            'name' => 'Vanh'
        ],
        [
            "machine_id" => 8,
            'account_id' => 8,
            'name' => 'Giang'
        ],
        [
            "machine_id" => 9,
            'account_id' => 2,
            'name' => 'Trí'
        ],
        [
            "machine_id" => 10,
            'account_id' => 25,
            'name' => 'Quý'
        ],
        [
            "machine_id" => 12,
            'account_id' => 6,
            'name' => 'Thọ'
        ],
        [
            'machine_id' => 14,
            'account_id' => 28,
            'name' => 'Khang'
        ],
        [
            'machine_id' => 15,
            'account_id' => 27,
            'name' => 'Minh'
        ],
        [
            'machine_id' => 16,
            'account_id' => 10,
            'name' => 'Tuan Anh'
        ],
        [
            'machine_id' => 17,
            'account_id' => 32,
            'name' => 'Quang'
        ],
        [
            'machine_id' => 18,
            'account_id' => 34,
            'name' => 'Mai'
        ],
        [
            'machine_id' => 19,
            'account_id' => 36,
            'name' => 'Thế Anh'
        ],
        [
            'machine_id' => 20,
            'account_id' => 35,
            'name' => 'Tùng Dương'
        ]
    ];

    public function checkInOut(Request $request)
    {
        $currentTime = Carbon::now();
        $startTime = Carbon::createFromTime(12, 0, 0); // Thời gian bắt đầu: 12:00
        $endTime = Carbon::createFromTime(13, 30, 0);  // Thời gian kết thúc: 13:30
        $arrId = [];
        $department = Department::where('name', 'Phòng Sale')->first()->id;
        $accountDepartments = AccountDepartment::where('department_id', $department)->get();
        $arrId = $accountDepartments->pluck('account_id')->toArray();
        $saleMembers = Account::whereIn('id', $arrId)->get();
        $arrAccountId = $saleMembers->pluck('id')->toArray();

        foreach ($request->attendances as $attendance) {
            $time = Carbon::parse($attendance['time'])->setTimezone('Asia/Ho_Chi_Minh');
            $eightThirty = $time->copy()->startOfDay()->addHours(8)->addMinutes(30);
            // Nếu điểm danh trước 8h30 thì sẽ thành điểm danh lúc 8h30
            if ($time->lessThan($eightThirty)) {
                $time = $eightThirty;
            }
            $time = $time->format('Y-m-d H:i:s');
            foreach (self::ARRAY_ID_RONALJACK as $item) {
                if ($attendance['user_id'] == $item['machine_id']) {
                    // Kiểm tra xem trong ngày hôm đó có điểm danh hay chưa
                    $attendance2 = Attendance::where('account_id', $item['account_id'])
                        ->whereDate('checkin', explode(' ', $time)[0])
                        ->first();
                    // check xem có trong khoảng giờ nghỉ trưa hay không
                    if (!$currentTime->between($startTime, $endTime)) {
                        if ($attendance2 != null) {
                            // Nếu cùng ngày mà chưa check out thì update checkout
                            if (
                                Carbon::parse($attendance2->checkin)->isSameDay($time)
                                && $attendance2->checkout == null
                            ) {
                                $attendance2->update([
                                    'checkout' => $time
                                ]);
                            } else {
                                if (in_array($item['account_id'], $arrAccountId)) {
                                    // Đối với người phòng sales, được phép điểm danh hơn 1 lần trong 1 ngày
                                    if (Carbon::parse($attendance2->checkin)->isSameDay($time)) {
                                        Attendance::query()
                                            ->create([
                                                'account_id' => $item['account_id'],
                                                'checkin' => $time
                                            ]);
                                    }
                                }
                            }
                        } else {
                            // Nếu không có điểm danh trong hôm đó thì điểm danh
                            Attendance::query()
                                ->create([
                                    'account_id' => $item['account_id'],
                                    'checkin' => $time
                                ]);
                        }
                    } else {
                        if (in_array($item['account_id'], $arrAccountId)) {
                            if ($attendance2 != null) {
                                // Nếu cùng ngày mà chưa check out thì update checkout
                                if (Carbon::parse($attendance2->checkin)->isSameDay($time) && $attendance2->checkout == null) {
                                    $attendance2->update([
                                        'checkout' => $time
                                    ]);
                                } else {
                                    // Đối với người phòng sales, được phép điểm danh hơn 1 lần trong 1 ngày
                                    if (Carbon::parse($attendance2->checkin)->isSameDay($time)) {
                                        Attendance::query()
                                            ->create([
                                                'account_id' => $item['account_id'],
                                                'checkin' => $time
                                            ]);
                                    }
                                }
                            } else {
                                // Nếu không có điểm danh trong hôm đó thì điểm danh
                                Attendance::query()
                                    ->create([
                                        'account_id' => $item['account_id'],
                                        'checkin' => $time
                                    ]);
                            }
                        }
                    }
                }
            }
        }
        return response()->json([
            'success' => 'Đã đồng bộ điểm danh lên hệ thống'
        ]);
    }
}
