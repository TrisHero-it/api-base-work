<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AccountDepartment;
use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index()
    {
        $departments = Department::all();
        foreach ($departments as $department) {
            $arrAccount = [];
            $accounts = AccountDepartment::query()->where('department_id', $department->id)->get();
            foreach ($accounts as $account) {
                $arrAccount[] = Account::query()->select('username', 'avatar','full_name', 'id')->find($account->account_id);
            }

            $department['members'] = $arrAccount;
        }

        return response()->json($departments);
    }

    public function store(Request $request)
    {
        $data = $request->all();
        $department = Department::query()->create([
            'name' => $data['name'],
        ]);
        foreach ($data['members'] as $member) {
            $account = Account::query()->where('username', $member)->first();
            AccountDepartment::query()->create([
                'department_id' => $department->id,
                'account_id' => $account->id,
            ]);
        }
        $department = array_merge($department->toArray(), $data);
        $department['id'] = 'department-' . $department['id'];

        return response()->json($department);
    }

    public function update(int $id, Request $request)
    {
        $department = Department::query()->findOrFail($id);
        $data = $request->all();
        $department->update($data);
        AccountDepartment::query()->where('department_id', $id)->delete();
        foreach ($data['members'] as $member) {
            $account = Account::query()->where('username', $member)->first();
            AccountDepartment::query()->create([
                'department_id' => $department->id,
                'account_id' => $account->id,
            ]);
        }
        $department['members'] = $data['members'];
        $department['id'] = 'department-' . $department['id'];

        return response()->json($department);
    }

    public function destroy(int $id)
    {
        $department = Department::query()->findOrFail($id);
        $department->delete();

        return response()->json([
            'success' => 'Xoá thành công'
        ]);
    }
}
