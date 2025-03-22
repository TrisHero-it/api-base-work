<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\LeaveHistory;
use Illuminate\Http\Request;

class LeaveHistoryController extends Controller
{
    public function index(Request $request)
    {
        $leaveHistories = LeaveHistory::all();

        return response()->json($leaveHistories);
    }

    public function store(Request $request)
    {
        $data = $request->all();
        $leaveHistory = LeaveHistory::create($data);
        $account = Account::find($data['account_id'])->update([
            'quit_work' => true
        ]);

        return response()->json($leaveHistory);
    }
}
