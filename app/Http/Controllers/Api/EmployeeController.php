<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Role;
use Carbon\Carbon;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $name = $request->search;
        $perPage = $request->per_page ?? 10;
        if (isset($request->include)) {
            $accounts = Account::with(['jobPosition.salary', 'department', 'workHistories', 'educations', 'familyMembers', 'dayoffAccount', 'contracts.category', 'contractActive']);
        } else {
            $accounts = Account::select(
                'id',
                'username',
                'full_name',
                'avatar',
                'role_id',
                'email',
                'phone',
                'quit_work'
            )->with('dayoffAccount');
        }
        if ($name != null) {
            $accounts = $accounts->where('full_name', 'like', "%$name%");
        }
        if ($request->filled('role_id')) {
            $accounts = $accounts->where('role_id', $request->role_id)
                ->where('quit_work', false);
        }
        if ($request->filled('quit_work')) {
            $accounts = $accounts->where('quit_work', $request->quit_work);
        } else {
            $accounts = $accounts->where('quit_work', false);
        }

        $accounts = $accounts->paginate($perPage);
        foreach ($accounts as $account) {
            if ($account->start_work_date != null) {
                $mocThoiGian = Carbon::parse($account->start_work_date); // mốc thời gian
                $hienTai = Carbon::now();
                $thoiGianLamViec = $mocThoiGian->diff($hienTai);
                $account->seniority = $thoiGianLamViec->y . ' năm ' . $thoiGianLamViec->m . ' tháng ' . $thoiGianLamViec->d . ' ngày';
            }
            if ($account->contractActive != null) {
                $account->name_contract = $account->contractActive['files'][0]['file_name'];
                $account->category__contract_id = $account->contractActive->category->name;
                $account->url_contract = $account->contractActive['files'][0]['file_url'];
                $account->contract_start_date = $account->contractActive->start_date;
                $account->contract_end_date = $account->contractActive->end_date;
                unset($account->contractActive);
            }
            if ($account->dayoffAccount != null) {
                $account->day_off = $account->dayoffAccount->total_holiday_with_salary + $account->dayoffAccount->seniority_holiday . " Ngày";
            }
            if ($account->jobPosition->where('status', 'active')->first() != null) {
                if (!empty($account->jobPosition)) {
                    $account->kpi = $account->jobPosition->where('status', 'active')->first()->salary->kpi;
                    $account->basic_salary = $account->jobPosition->where('status', 'active')->first()->salary->basic_salary;
                    $account->travel_allowance = $account->jobPosition->where('status', 'active')->first()->salary->travel_allowance;
                    $account->eat_allowance = $account->jobPosition->where('status', 'active')->first()->salary->eat_allowance;
                } else {
                    $account->kpi = 0;
                    $account->basic_salary = 0;
                    $account->travel_allowance = 0;
                    $account->eat_allowance = 0;
                }
                $account->position = $account->jobPosition->where('status', 'active')->first()->name;
            } else {
                $account->position = null;
            }
            if (!empty($account->department->toArray())) {
                $account->department_name = $account->department[0]->name;
                unset($account->department);
            }
            $roles = Role::query()->get();
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
        }
        $a = Account::query();

        if ($name != null) {
            $a = $a->where('full_name', 'like', "%$name%");
        }
        if ($request->filled('role_id')) {
            $a = $a->where('role_id', $request->role_id)
                ->where('quit_work', false);
        }
        if ($request->filled('quit_work')) {
            $a = $a->where('quit_work', $request->quit_work);
        }
        $a = $a->get();
        $countRoleAccount = [
            'Tất cả' => $a->count(),
            $roles->where('id', 1)->first()->name => $a->where('role_id', 1)->where('quit_work', false)->count(),
            $roles->where('id', 2)->first()->name => $a->where('role_id', 2)->where('quit_work', false)->count(),
            $roles->where('id', 3)->first()->name => $a->where('role_id', 3)->where('quit_work', false)->count(),
            'Vô hiệu hoá' => $a->where('quit_work', true)->count(),
        ];

        return response()->json([
            'current_page' => $accounts->currentPage(),
            'data' => $accounts->items(),
            'first_page_url' => $accounts->url(1),
            'from' => $accounts->firstItem(),
            'last_page' => $accounts->lastPage(),
            'last_page_url' => $accounts->url($accounts->lastPage()),
            'links' => $accounts->links(),
            'next_page_url' => $accounts->nextPageUrl(),
            'path' => $accounts->path(),
            'per_page' => $accounts->perPage(),
            'prev_page_url' => $accounts->previousPageUrl(),
            'to' => $accounts->lastItem(),
            'total' => $accounts->total(),
            'count_role_account' => $countRoleAccount,
        ]);
    }
}
