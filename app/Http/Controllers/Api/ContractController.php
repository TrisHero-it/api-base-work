<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

    public function store(Request $request)
    {
        $data = $request->except('active');
        $data['creator_by'] = Auth::id();
        if ($request->filled('active')) {
            Contract::where('account_id', $request->account_id)
                ->where('active', true)
                ->update(['active' => false]);
            $data['active'] = true;
        }
        $contract = Contract::create($data);

        return response()->json($contract);
    }
}
