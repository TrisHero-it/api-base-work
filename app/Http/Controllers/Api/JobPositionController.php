<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\JobPosition;
use App\Models\Salary;
use Illuminate\Http\Request;

class JobPositionController extends Controller
{
    public function index(Request $request)
    {
        if ($request->filled('account_id')) {
            $jobPositions = JobPosition::where('account_id', $request->account_id)->get();
        } else {
            $jobPositions = JobPosition::all();
        }

        return response()->json($jobPositions);
    }

    public function store(Request $request)
    {
        $jobPosition = JobPosition::latest()
            ->where('status', 'active')
            ->first();
        if ($jobPosition) {
            $salary = Salary::where('job_position_id', $jobPosition->id)->first();
        } else {
            $salary = null;
        }
        $account = Account::find($request->account_id);
        $dataAccount = $request->except('position', 'description', 'department_id', 'basic_salary', 'kpi', 'travel_allowance', 'eat_allowance');
        $dataJobPosition = [];
        $dataSalary = [];
        if ($request->filled('department_id')) {
            $dataAccount['department_id'] = $request->department_id;
        }
        if (isset($jobPosition)) {
            $jobPosition->update([
                'status' => 'inactive'
            ]);
        }

        $dataJobPosition['name'] = $request->new_position ?? $jobPosition->name;
        $dataJobPosition['account_id'] = $request->account_id;
        $dataJobPosition['status'] = 'active';
        $dataJobPosition['description'] = $request->description ?? $jobPosition->description;

        $jobPosition = JobPosition::create($dataJobPosition);

        $dataSalary['basic_salary'] = $request->basic_salary ?? $salary->basic_salary;
        $dataSalary['kpi'] = $request->kpi ?? $salary->kpi;
        $dataSalary['travel_allowance'] = $request->travel_allowance ?? $salary->travel_allowance;
        $dataSalary['eat_allowance'] = $request->eat_allowance ?? $salary->eat_allowance;
        $dataSalary['job_position_id'] = $jobPosition->id;

        $salary = Salary::create($dataSalary);

        $account->update($dataAccount);
        return response()->json([
            'message' => 'Job position created successfully',
            'job_position' => $jobPosition,
            'salary' => $salary
        ]);
    }
}
