<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JobPosition;
use App\Models\Salary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SalaryTableController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->input("date");
        $year = explode("-", $date)[0];
        $month = explode("-", $date)[1];
        $basicSalary = JobPosition::where('id', Auth::id())
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->first();

        $salary = Salary::where('job_position_id', $basicSalary->id)->get();
        
        return response()->json([ 
            'salary' => $salary,
        ]);
    }
}
