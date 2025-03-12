<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Education;
use App\Models\FamilyMember;
use App\Models\Salary;
use App\Models\WorkHistory;
use Illuminate\Http\Request;

class StaffController extends Controller
{
    public function index(Request $request)
    {
        $staffs = Account::where('active', true)->get();

        return response()->json($staffs);
    }

    public function store(Request $request)
    {
        $staff = Account::where('id', $request->account_id)->first();

        $staff->update($request->personal_info);
        if ($request->filled('salary')) {
            $arrSalary = ['account_id' => $request->account_id];
            $arrSalary = array_merge($arrSalary, $request->salary);
            Salary::create($arrSalary);
        }
        if ($request->filled('education')) {
            $arrEducation = ['account_id' => $request->account_id];
            $arrEducation = array_merge($arrEducation, $request->education);
            Education::create($arrEducation);
        }
        if ($request->filled('work_history')) {
            $arrWorkHistory = ['account_id' => $request->account_id];
            $arrWorkHistory = array_merge($arrWorkHistory, $request->work_history);
            WorkHistory::create($arrWorkHistory);
        }
        if ($request->filled('family_info')) {
            $arrFamilyInfo = ['account_id' => $request->account_id];
            $arrFamilyInfo = array_merge($arrFamilyInfo, $request->family_info);
            FamilyMember::create($arrFamilyInfo);
        }

        return response()->json($staff);
    }
}
