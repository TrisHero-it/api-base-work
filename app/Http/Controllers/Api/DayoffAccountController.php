<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\DayoffAccount;
use Illuminate\Http\Request;

class DayoffAccountController extends Controller
{
    public function index()
    {
        $dayoffAccounts = DayoffAccount::all();
        return response()->json($dayoffAccounts);
    }

    public function store(Request $request)
    {
        $dayoffAccount = DayoffAccount::create($request->all());
        return response()->json($dayoffAccount);
    }

    public function update(Request $request, $id)
    {
        $account = Account::find($id);
        $dayoffAccount = DayoffAccount::where('account_id', $account->id)->first();
        $dayoffAccount->update($request->all());
        $account->dayoffAccount = $dayoffAccount;

        return response()->json($account);
    }
}
