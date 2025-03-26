<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Education;
use App\Models\FamilyMember;
use App\Models\JobPosition;
use App\Models\Salary;
use App\Models\WorkHistory;
use Illuminate\Http\Request;

class StaffController extends Controller
{
    public function index(Request $request)
    {
        $staffs = Account::where('active', true)
            ->where('quit_work', false)
            ->get();

        return response()->json($staffs);
    }

    public function store(Request $request)
    {
        $account = Account::where('id', $request->account_id)
            ->first();
        $data = $request->except('account_id', 'educations', 'work_histories', 'family_members', 'job_positions', 'salary', 'position');
        $data['is_active'] = true;

        if ($request->filled('position')) {
            $jobPosition = JobPosition::create([
                'name' => $request->position ?? 'Thực tập sinh',
                'account_id' => $request->account_id,
                'status' => 'active',
            ]);

            if ($request->filled('salary')) {
                $salary = Salary::create([
                    'basic_salary' => $request->salary->basic_salary,
                    'job_position_id' => $jobPosition->id,
                    'travel_allowance' => $request->salary->travel_allowance,
                    'eat_allowance' => $request->salary->eat_allowance,
                    'kpi' => $request->salary->kpi,
                ]);
            }

            $models = [
                'educations' => Education::class,
                'work_histories' => WorkHistory::class,
                'family_members' => FamilyMember::class,
            ];

            foreach ($models as $key => $model) {
                if ($request->filled($key)) {
                    $arr = [];
                    foreach ($request->$key as $a) {
                        $arr[] = array_merge(['account_id' => $request->account_id], $a);
                    }
                    $model::insert($arr);
                }
            }
        }
        $account->update($data);


        return response()->json($account);
    }
}
