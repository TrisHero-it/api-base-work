<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\JobPosition;
use App\Models\Salary;
use Illuminate\Http\Request;

class PromoteController extends Controller
{

    public function store(Request $request)
    {
        $data = $request->safe()->except('personnel_class', 'basic_salary', 'travel_allowance', 'eat_allowance', 'kpi', 'postion');
        $data['is_active'] = true;
        $account = Account::find($data['account_id']);

        if ($request->filled('position')) {
            $jobPosition = JobPosition::where('account_id', $data['account_id'])->where('status', 'active')->first();
            $jobPosition->update([
                'status' => 'inactive',
            ]);
            $jobPosition = JobPosition::create(
                [
                    'account_id' => $data['account_id'],
                    'name' => $request->position,
                    'status' => 'active',
                    'old_postion' => $jobPosition->name,
                ]
            );
            $salary = Salary::create([
                'job_position_id' => $jobPosition->id,
                'basic_salary' => $request->basic_salary,
                'travel_allowance' => $request->travel_allowance,
                'eat_allowance' => $request->eat_allowance,
                'kpi' => $request->kpi,
            ]);
        }

        $account = Account::update($data);

        return response()->json($account);
    }
}
