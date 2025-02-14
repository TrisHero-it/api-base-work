<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Account;
class ScheduleAccountController extends Controller
{
    public function index(Request $request)
    {
        $accounts = Account::all();
        foreach ($accounts as $account) {
            
        }
        return response()->json($accounts);
    }
}
