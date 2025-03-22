<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Asset;
use Illuminate\Http\Request;

class AssetAccountController extends Controller
{
    public function index(Request $request)
    {
        $accounts = Account::where('quit_work', false)->get();

        return response()->json($accounts);
    }
}
