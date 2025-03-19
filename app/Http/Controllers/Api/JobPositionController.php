<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JobPosition;
use Illuminate\Http\Request;

class JobPositionController extends Controller
{
    public function index(Request $request)
    {
        if ($request->filled('account_id')) {
            $jobPositions = JobPosition::where('account_id', $request->account_id)->get();
        } else {
            $jobPositions = JobPosition::all();
        }

        return response()->json($jobPositions);
    }

}
