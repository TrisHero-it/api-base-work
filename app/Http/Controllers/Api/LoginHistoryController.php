<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HistoryLogin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class   LoginHistoryController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->per_page ?? 10;
        $loginHistories = HistoryLogin::paginate($perPage);

        return response()->json($loginHistories);
    }

    public function store(Request $request)
    {
        $loginHistory = HistoryLogin::create([
            'account_id' => Auth::id(),
            'ip_address' => explode(',', $request->header('X-Forwarded-For'))[0],
            'user_agent' => $request->header('User-Agent'),
            'method' => 'account',
        ]);

        return response()->json($loginHistory);
    }
}
