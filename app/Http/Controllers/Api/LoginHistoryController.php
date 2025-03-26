<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HistoryLogin;
use Illuminate\Http\Request;

class   LoginHistoryController extends Controller
{
    public function index(Request $request)
    {
        $loginHistories = HistoryLogin::all();
        return response()->json($loginHistories);
    }
}
