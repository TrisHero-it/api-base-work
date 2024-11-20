<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\HistoryMoveTask;
use App\Models\Kpi;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KpiController extends Controller
{
    public function index(Request $request) {
        $accounts = Account::all();
        foreach ($accounts as $account) {
            $account['CompletedOnTime'] = Kpi::query()->where('status', 1)->where('account_id', $account->id)->get()->count();
            $account['CompletedLate'] = Kpi::query()->where('status', 0)->where('account_id', $account->id)->get()->count();
            $account['InProgress'] = Task::query()->where('account_id', $account->id)->where('expired', '>',now())->get()->count();
            $account['Overdue'] = Task::query()->where('account_id', $account->id)->where('expired', '<',now())->get()->count();
        }

        return response()->json($accounts);
    }
}
