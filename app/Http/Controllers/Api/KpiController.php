<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AccountProfile;
use App\Models\HistoryMoveTask;
use App\Models\Kpi;
use App\Models\Stage;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KpiController extends Controller
{
    public function index(Request $request) {
        $stages = Stage::query()->where('workflow_id', $request->workflow_id)->where('index', '!=', '1')->where('index', '!=', '0')->orderBy('index', 'desc')->get();
        $accounts = Account::query()->select('id')->get();
        foreach ($stages as $stage) {
            foreach ($accounts as $account) {
                $kpi =  Kpi::query()->where('stage_id', $stage->id)->where('account_id', $account->id)->get()->count();
                $account['CompletedOnTime'] = $kpi;
                $account['full_name'] = AccountProfile::query()->where('email', $account->id)->first()->full_name;
            }
            $stage['accounts'] = $accounts;
        }

        return response()->json($stages);
    }
}
