<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\LeaveHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeaveHistoryController extends Controller
{
    // good, bad, normal
    public function index(Request $request)
    {
        $leaveHistories = LeaveHistory::query();
        if ($request->filled('type')) {
            $leaveHistories = $leaveHistories->where('type', $request->type);
        }

        if (!Auth::user()->isSeniorAdmin()) {
            $leaveHistories = $leaveHistories->where('account_id', Auth::id());
        } else {
            if ($request->filled('account_id')) {
                $leaveHistories = $leaveHistories->where('account_id', $request->account_id);
            }
        }

        $leaveHistories = $leaveHistories->get();
        
        return response()->json($leaveHistories);
    }

    public function store(Request $request)
    {
        $data = $request->all();
        LeaveHistory::where('status', 'active')
            ->where('account_id', $request->account_id)
            ->update([
                'status' => 'inactive'
            ]);
        $data['status'] = 'active';
        $leaveHistory = LeaveHistory::create($data);
        if ($leaveHistory->type == 'quit') {
            $obj = new AccountController();
            $obj->disableAccount($request->account_id);
        }

        return response()->json($leaveHistory);
    }
}
