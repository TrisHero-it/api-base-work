<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Attendance;
use App\Models\DateHoliday;
use App\Models\Propose;
use App\Models\ProposeCategory;
use App\Models\Role;
use Carbon\Carbon;
use GuzzleHttp\Handler\Proxy;
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
            $date = Carbon::parse($request->date);
        } else {
            $month2 = now()->month;
            $year2 = now()->year;
            $date = now();
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

        $roles = Role::query()->get();

        foreach ($accounts as $account) {
            if ($account->dayoffAccount != null) {
                $account->day_off = $account->dayoffAccount->dayoff_count + $account->dayoffAccount->dayoff_long_time_worker;
                unset($account->dayoffAccount);
            }
            if ($account->quit_work == true) {
                $account['role'] = 'Vô hiệu hoá';
            } else {
                if ($account->role_id == 2) {
                    $account['role'] = $roles->where('id', 2)->first()->name;
                } else if ($account->role_id == 3) {
                    $account['role'] = $roles->where('id', 3)->first()->name;
                } else {
                    $account['role'] = $roles->where('id', 1)->first()->name;
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
            $isSalesMember = Auth::user()->isSalesMember();
            foreach ($newAttendances as $newAttendance) {
                $hours = 0;
                $workday = 0;
                $checkout = null;
                $checkin = Carbon::parse($newAttendance->checkin);
                $checkout = Carbon::parse($newAttendance->checkout);
                $noonTime = $checkin->copy()->setHour(value: 13)->setMinute(30)->setSecond(0);
                if ($newAttendance->checkout != null) {
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
                $workday = number_format($hours, 2) / 7.5;
                if ($workday > 1) {
                    $workday = 1;
                }
                $totalWorkDay += $workday;
            }
            $wfhId = ProposeCategory::where('name', 'Đăng ký WFH')->first()->id;
            $wfh = Propose::where('propose_category_id', $wfhId)
                ->where('account_id', $account->id)
                ->where('status', operator: 'approved')
                ->whereMonth('date_wfh', $date->month)
                ->count();
            $wfh = $wfh * 0.8;
            $wfh = number_format($wfh, 3);
            $account['day_off_used'] = $a;
            $account['hours_over_time'] = number_format($hoursOT, 2);
            $account['workday'] = $totalWorkDay == 0 ? number_format($totalWorkDay + $wfh, 3) : number_format($totalWorkDay + $wfh, 3);
        }

        return response()->json($accounts);
    }
}
