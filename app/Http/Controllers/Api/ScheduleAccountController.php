<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;
use App\Models\Account;
class ScheduleAccountController extends Controller
{
    public function index(Request $request)
    {
        $accounts = Account::select('email', 'full_name', 'avatar', 'id', 'position')->get();
        if (isset($request->date)) {
            $date = $request->date;
        } else {
            $date = now()->format('Y-m-d');
        }
        $taskA = Task::whereDate('started_at', $date)->get();
        return response()->json($taskA);
        foreach ($accounts as $account) {

        }
        return response()->json($accounts);
    }
}
