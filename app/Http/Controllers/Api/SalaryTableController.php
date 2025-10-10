<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AccountDepartment;
use App\Models\Attendance;
use App\Models\DateHoliday;
use App\Models\Department;
use App\Models\JobPosition;
use App\Models\Propose;
use App\Models\ProposeCategory;
use App\Models\Role;
use App\Models\Salary;
use App\Models\SalaryMonth;
use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SalaryTableController extends Controller
{
    public function index(Request $request)
    {
        // if (Auth::id() == 11 || Auth::id() == 25) {
        if (Auth::user()->isSeniorAdmin()) {
            # code...
            if ($request->filled('date') && $request->filled('salary_closed')) {
                $a = explode('-', $request->date);
                $month = $a[1];
                $year = $a[0];

                $salaryTable = SalaryMonth::with("account")->where('month', $month)
                    ->where('year', $year)
                    ->get();
                $accountDepartments = AccountDepartment::all();
                $departments = Department::all();
                $jobPositionActive = JobPosition::where('status', 'active')->get();
                foreach ($salaryTable as $salary) {
                    $salary->salary_detail = $salary->salary;
                    $salary->avatar = $salary->account->avatar;
                    $salary->email = $salary->account->email;
                    $salary->full_name = $salary->account->full_name;
                    $salary->workday = $salary->salary['workday'] ?? 0;
                    $salary->username = $salary->account->username;
                    $salary->workday_in_month = $salary->salary['workday_in_month'] ?? 0;
                    $departmentId = $accountDepartments->where('account_id', $salary->account->id)->first();
                    if ($departmentId != null) {
                        $department = $departments->where('id', $departmentId->department_id)->first();
                        $salary->departments = $department->name;
                    }

                    $position = $jobPositionActive->where('account_id', $salary->account->id)->first();
                    if ($position != null) {
                        $salary->position = $position->name;
                    }

                    if ($salary->account->role_id == 1) {
                        $salary->role = 'Nhân viên';
                    } else if ($salary->account->role_id == 2) {
                        $salary->role = 'Quản lý';
                    } else {
                        $salary->role = 'Admin';
                    }

                    unset($salary->account);
                    unset($salary->salary);
                }
                return response()->json($salaryTable);
            }

            if ($request->filled('date')) {
                $a = explode('-', $request->date);
                $month = $a[1];
                $year = $a[0];
            } else {
                $month = Carbon::now()->month;
                $year = Carbon::now()->year;
            }

            $accounts = Account::with(['jobPositionActive', 'department'])
                ->where('quit_work', false)
                ->select('id', 'email', 'avatar', 'full_name', 'username', 'role_id', 'quit_work')
                ->get();

            foreach ($accounts as $account) {
                if ($account->role_id == 1) {
                    $account->role = 'Nhân viên';
                } else if ($account->role_id == 2) {
                    $account->role = 'Quản lý';
                } else {
                    $account->role = 'Admin';
                }

                $account->workday_in_month = Schedule::query()
                    ->whereMonth('day_of_week', $month)
                    ->whereYear('day_of_week', $year)
                    ->where('go_to_work', true)
                    ->count();

                $account->workday = $this->workDay($account->id, $request)->original['workday'];
                if ($account->jobPositionActive != null) {
                    $account->position = $account->jobPositionActive->name;
                    unset($account->jobPositionActive);
                }
                if ($account->department != null) {
                    foreach ($account->department as $dept) {
                        $account->departments = $dept->name;
                        break;
                    }
                    unset($account->department);
                }

                $basicSalary = JobPosition::where('status', 'active');
                $jobPosition = $basicSalary->where('account_id', $account->id)->first();

                if ($jobPosition != null) {
                    $salary = Salary::where('job_position_id', $jobPosition->id)->first();
                    $account->salary = $salary->basic_salary + $salary->travel_allowance + $salary->eat_allowance + $salary->kpi;
                    $account->salary_detail = $salary;
                } else {
                    $account->salary = null;
                    $account->salary_detail = null;
                }
            }

            return response()->json($accounts);
        }
        // } else {
        //     return response()->json(['message' => 'Bạn không có quyền truy cập'], 403);
        // }

    }

    public function store(Request $request)
    {
        $dataNotification = [];
        foreach ($request->all() as $value) {
            $data[] = [
                'account_id' => $value['account_id'],
                'salary' => json_encode($value['salary']),
                'month' => $value['month'],
                'year' => $value['year'],
            ];
            $dataNotification[] = [
                'title' => "Bảng lương của bạn đã được cập nhật",
                "message" => "Bảng lương của bạn đã được cập nhật, vui lòng kiểm tra",
                "account_id" => $value['account_id'],
                "link" => "",
                "manager_id" => Auth::user()->id,
            ];
        }

        $salaryTable = SalaryMonth::insert($data);

        return response()->json($salaryTable);
    }

    public function deleteWithDate($date)
    {
        $month = explode('-', $date)[0];
        $year = explode('-', $date)[1];
        $salaryTable = SalaryMonth::where('month', $month)
            ->where('year', $year)
            ->delete();

        return response()->json(['success' => 'Xóa thành công']);
    }

    private function workDay($id, Request $request)
    {
        $account = Account::select(
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
            ->where('id', $id)
            ->where('quit_work', false)
            ->first();

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


        return response()->json($account);
    }

    public function show($id, Request $request)
    {
        if ($request->filled('date')) {
            $a = explode('-', $request->date);
            $month = $a[1];
            $year = $a[0];

            $salaryTable = SalaryMonth::with("account")->where('month', $month)
                ->where('year', $year)
                ->where('account_id', $id)
                ->get();
            $accountDepartments = AccountDepartment::all();
            $departments = Department::all();
            $jobPositionActive = JobPosition::where('status', 'active')->get();
            foreach ($salaryTable as $salary) {
                $salary->avatar = $salary->account->avatar;
                $salary->email = $salary->account->email;
                $salary->full_name = $salary->account->full_name;
                $salary->username = $salary->account->username;

                $departmentId = $accountDepartments->where('account_id', $salary->account->id)->first();
                if ($departmentId != null) {
                    $department = $departments->where('id', $departmentId->department_id)->first();
                    $salary->department = $department->name;
                }

                $position = $jobPositionActive->where('account_id', $salary->account->id)->first();
                if ($position != null) {
                    $salary->position = $position->name;
                }

                if ($salary->account->role_id == 1) {
                    $salary->role = 'Nhân viên';
                } else if ($salary->account->role_id == 2) {
                    $salary->role = 'Quản lý';
                } else {
                    $salary->role = 'Admin';
                }

                unset($salary->account);
            }

            return response()->json($salaryTable);
        }
    }
}
