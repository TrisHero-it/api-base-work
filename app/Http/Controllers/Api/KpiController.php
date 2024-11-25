<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AccountProfile;
use App\Models\AccountWorkflow;
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
        $accounts = AccountWorkflow::query()->select('id', 'account_id')->where('workflow_id', $request->workflow_id)->get();
            foreach ($accounts as $account) {
                $account['full_name'] = AccountProfile::query()->where('email', $account->account_id)->value('full_name');
                foreach ($stages as $stage) {
                    $account[$stage->name] = Kpi::query()->where('stage_id', $stage->id)->where('account_id', $account->id)->get()->count();
                }
            }

        return response()->json($accounts);
    }
}
