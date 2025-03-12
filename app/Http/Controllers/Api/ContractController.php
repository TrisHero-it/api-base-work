<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use Illuminate\Http\Request;

class ContractController extends Controller
{
    public function index(Request $request)
    {
        if ($request->filled('account_id')) {
            $contracts = Contract::where('account_id', $request->account_id)->get();    
        } else {
            $contracts = Contract::all();
        }

        return response()->json($contracts);
    }

    
}
