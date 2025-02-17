<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Auth;
use Illuminate\Http\Request;
use App\Models\Account;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
class ScheduleAccountController extends Controller
{
    public function index(Request $request)
    {
        $accounts = Account::select('email', 'full_name', 'avatar', 'id', 'position')->get();
        if (isset($request->date)) {
            $date = $request->date;
        } else {
            $date = now()->format('Y-m-d');
        }
        $token = "Bearer " . Auth::user()->remember_token;
        $taskA = Task::whereDate('started_at', $date)->get();
        $data = Http::withHeaders([
            'Authorization' => $token
        ])->get("https://work.1997.pro.vn/api/schedule");
        $data = $data->json();
        $data = $data[$date];
        
        $innerStart1 = Carbon::parse($date->format("Y-m-d") . " 08:30:00");
        $innerEnd1 = Carbon::parse($date->format("Y-m-d") . " 12:00:00");
        $innerStart2 = Carbon::parse($date->format("Y-m-d") . " 13:30:00");
        $innerEnd2 = Carbon::parse($date->format("Y-m-d") . " 17:30:00");
        foreach ($accounts as $account) {
            $newData = array_filter($data, function ($item) use ($account) {
                return $item['account_id'] == $account->id;
            });
            
            return response()->json($newData);
        }
        return response()->json($accounts);
    }
}
