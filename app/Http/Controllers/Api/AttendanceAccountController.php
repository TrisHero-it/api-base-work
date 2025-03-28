<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Attendance;
use App\Models\DateHoliday;
use App\Models\Propose;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceAccountController extends Controller
{
    public function index(Request $request)
    {
        $accounts = Account::select(
            'id',
            'username',
            'full_name',
            'avatar',
            'role_id',
            'email',
            'phone',
            'quit_work',
        )
            ->with('dayoffAccount')
            ->where('quit_work', false)
            ->get();

        if (isset($request->date)) {
            $b = explode('-', $request->date);
            $month2 = $b[1];
            $year2 = $b[0];
        } else {
            $month2 = now()->month;
            $year2 = now()->year;
        }
        $proposes = Propose::where('status', 'approved')
            ->whereIn('name', ['Nghỉ có hưởng lương', 'Đăng ký OT'])
            ->whereMonth('created_at', $month2 ?? now()->month)
            ->whereYear('created_at', $year2 ?? now()->year)
            ->get();
        // Lấy ra tất cả các ngày xin nghỉ
        $arrIdHoliday = $proposes->where('name', 'Nghỉ có hưởng lương')->pluck('id');
        $arrIdOverTime = $proposes->where('name', 'Đăng ký OT')->pluck('id');
        $dateHolidays = DateHoliday::whereIn('propose_id', array_merge($arrIdHoliday->toArray(), $arrIdOverTime->toArray()))
            ->get();
        $holidays = $dateHolidays->whereIn('propose_id', $arrIdHoliday);
        $overTime = $dateHolidays->whereIn('propose_id', $arrIdOverTime);

        $attendances = Attendance::whereMonth('checkin', $month2)
            ->whereYear('checkin', $year2)
            ->get();

        foreach ($accounts as $account) {
            if ($account->dayoffAccount != null) {
                $account->day_off = $account->dayoffAccount->dayoff_count + $account->dayoffAccount->dayoff_long_time_worker;
                unset($account->dayoffAccount);
            }
            if ($account->quit_work == true) {
                $account['role'] = 'Vô hiệu hoá';
            } else {
                if ($account->role_id == 2) {
                    $account['role'] = 'Quản trị';
                } else if ($account->role_id == 3) {
                    $account['role'] = 'Quản trị cấp cao';
                } else {
                    $account['role'] = 'Thành viên thông thường';
                }
            }
            $a = 0;
            $hoursOT = 0;
            $accountHoliday = $proposes->where('account_id', $account->id)
                ->where('name', 'Nghỉ có hưởng lương')
                ->pluck('id');
            $accountHoliday = array_values($holidays->whereIn('propose_id', $accountHoliday)->toArray());
            foreach ($accountHoliday as $item) {
                $a += $item['number_of_days'];
            }
            $accountOverTime = $proposes->where('account_id', $account->id)
                ->where('name', 'Đăng ký OT')
                ->pluck('id');
            $accountOverTime = array_values($overTime->whereIn('propose_id', $accountOverTime)->toArray());
            foreach ($accountOverTime as $item) {
                $hoursOT += Carbon::parse($item['end_date'])->floatDiffInHours(Carbon::parse($item['start_date']));
            }
            $totalWorkDay = 0;
            // Lọc từng tài khoản để tính ngày công
            $newAttendances = null;
            $newAttendances = $attendances->where('account_id', $account->id);
            foreach ($newAttendances as $newAttendance) {
                $diff = 0;
                $hours = 0;
                $workday = 0;
                $checkout = null;
                if ($newAttendance->checkout != null) {
                    $checkout = Carbon::parse($newAttendance->checkout);
                    $diff = $checkout->diffInMinutes($newAttendance->checkin);
                    $hours = $diff / 60;
                    $workday = $hours / 9;
                }
                $totalWorkDay += $workday;
            }
            $account['day_off_used'] = $a;
            $account['hours_over_time'] = number_format($hoursOT, 2);
            $account['workday'] = $totalWorkDay == 0 ? $totalWorkDay : number_format($totalWorkDay, 3);
        }

        return response()->json($accounts);
    }
}
