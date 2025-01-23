<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AccountDepartment;
use App\Models\Attendance;
use App\Models\Department;
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
        if (isset($request->me)) {
            $account = Auth::user();
            $attendance = Attendance::query()
                ->where('account_id', $account->id)
                ->whereDate('checkin', Carbon::today())
                ->orderBy('id')
                ->get();
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
        }

        return response()->json($attendance);
    }

    public function checkIn(Request $request)
    {
        $currentTime = Carbon::now();
        $startTime = Carbon::createFromTime(12, 0, 0); // Thời gian bắt đầu: 12:00
        $endTime = Carbon::createFromTime(13, 30, 0);  // Thời gian kết thúc: 13:30
        $attendance = Attendance::where('account_id', Auth::id())->latest('id')->first();
        $arrId = [];
        $department = Department::where('name', 'Phòng sale')->first()->id;
        $accountDepartments = AccountDepartment::where('department_id', $department)->get();
        foreach ($accountDepartments as $accountDepartment) {
            $arrId[] = $accountDepartment->account_id;
        }
        $saleMembers = Account::whereIn('id', $arrId)->get()->toArray();
        $arrAccountId = [];
        foreach ($saleMembers as $saleMember) {
            $arrAccountId[] = $saleMember['id'];
        }
        if (!$currentTime->between($startTime, $endTime)) {
            if (empty($attendance) || in_array(Auth::id(), $arrAccountId)) {
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
                return response()
                    ->json([
                        'error' => 'Hôm nay bạn đã điểm danh rồi'
                    ], 403);
            }
        } else {
            return response()
                ->json([
                    'error' => 'Không được điểm danh vào thời gian này'
                ], 403);
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
