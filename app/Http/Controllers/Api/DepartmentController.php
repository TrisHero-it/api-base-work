<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AccountDepartment;
use App\Models\Department;
use App\Models\Workflow;
use App\Models\WorkflowCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DepartmentController extends Controller
{
    public function index(Request $request)
    {
        if (isset($request->department_id)) {
            $departments = Department::query()->where('id', $request->department_id)->get();
        } else {
            $departments = Department::query()->get();
        }
        $arrDepartmentId = $departments->pluck('id');
        $workflowCategories = WorkflowCategory::query()->whereIn('department_id', $arrDepartmentId)->get();
        $accounts = AccountDepartment::query()->whereIn('department_id', $arrDepartmentId)->get();
        $arrAccountId = $accounts->pluck('account_id')->toArray();
        $members = Account::query()
            ->select('id', 'full_name', 'avatar')
            ->whereIn('id', $arrAccountId)->get();
        foreach ($departments as $department) {
            $workflowCategory = $workflowCategories->where('department_id', $department->id)->first()->id;
            $workflows = Workflow::query()->where('workflow_category_id', $workflowCategory)->get();
            $department['projects'] = $workflows;
            $accounts2 = $accounts->where('department_id', $department->id);
            $members2 = $members->whereIn('id', $accounts2->pluck('account_id'));
            $members2 = array_values($members2->toArray());
            $department['members'] = $members2;
        }

        return response()->json($departments);
    }

    public function store(Request $request)
    {
        if (Auth::user()->isSeniorAdmin()) {
            $data = $request->all();
            $department = Department::query()->create([
                'name' => $data['name'],
            ]);
            foreach ($data['members'] as $member) {
                AccountDepartment::query()->create([
                    'department_id' => $department->id,
                    'account_id' => $member,
                ]);
            }
            $department = array_merge($department->toArray(), $data);
            return response()->json($department);
        } else {
            return response()->json([
                'error' => 'Bạn không có quyền thực hiện hành động này'
            ], 403);
        }
    }

    public function update(int $id, Request $request)
    {
        if (Auth::user()->isSeniorAdmin()) {
            $data = $request->all();
            $department = Department::query()->findOrFail($id);
            $department->update($data);

            if (isset($data['members'])) {
                AccountDepartment::where('department_id', $id)->delete();
                foreach ($data['members'] as $member) {
                    AccountDepartment::query()->create([
                        'department_id' => $id,
                        'account_id' => $member,
                    ]);
                }
            }

            return response()->json($department);
        } else {
            return response()->json([
                'error' => 'Bạn không có quyền thực hiện hành động này'
            ], 403);
        }
    }

    public function show(int $id)
    {
        $department = Department::query()->findOrFail($id);
        $arrAccount = [];
        $accounts = AccountDepartment::query()->where('department_id', $department->id)->get();
        foreach ($accounts as $account) {
            $arrAccount[] = Account::query()->select('username', 'avatar', 'full_name', 'id')->find($account->account_id);
        }

        $department['members'] = $arrAccount;
        return response()->json($department);
    }

    public function destroy(int $id)
    {
        if (Auth::user()->isSeniorAdmin()) {
            $department = Department::query()->findOrFail($id);
            $department->delete();

            return response()->json([
                'success' => 'Xoá thành công'
            ]);
        } else {
            return response()->json([
                'error' => 'Bạn không có quyền thực hiện hành động này'
            ], 403);
        }
    }
}
