<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AccountWorkflow;
use App\Models\Kpi;
use App\Models\Stage;
use App\Models\Task;
use Illuminate\Http\Request;

class KpiController extends Controller
{
    public function index(Request $request) {

        if (isset($request->date)) {
           $date = explode('-', $request->date);
           $month = $date[1];
           $year = $date[0];
        }else {
            $month = date('m');
            $year = date('Y');
        }
        $stages = Stage::query()
            ->where('workflow_id', $request->workflow_id)
            ->where('index', '!=', '1')
            ->where('index', '!=', '0')
            ->orderBy('index', 'desc')
            ->get();
        $accounts = AccountWorkflow::query()
            ->select('id', 'account_id')
            ->where('workflow_id', $request->workflow_id)
            ->get();
            foreach ($accounts as $account) {
                $account['Người thực thi'] = Account::query()->where('id', $account->account_id)->value('full_name');
                foreach ($stages as $stage) {
                    if (isset($request->tag_id)) {
                        $arrTag = explode(',', $request->tag_id);
                        $tasks = Task::whereHas('tags', function($query) use ($arrTag) {
                            $query->whereIn('stickers.id', $arrTag);
                        })->get();
                        $totalA = 0;
                        $totalB = 0;
                        foreach ($tasks as $task) {
                            $kpi = Kpi::query()
                                ->where('stage_id', $stage->id)
                                ->where('account_id', $account->account_id)
                                ->whereYear('updated_at', $year)
                                ->whereMonth('updated_at', $month)
                                ->where('status', 0)
                                ->where('task_id', $task->id)
                                ->get()->count();
                            $totalA += $kpi;
                            $failedKpi = Kpi::query()
                                ->where('stage_id', $stage->id)
                                ->where('account_id', $account->account_id)
                                ->whereYear('updated_at', $year)
                                ->whereMonth('updated_at', $month)
                                ->where('status', 1)->get()
                                ->where('task_id', $task->id)
                                ->count();
                            $totalB += $failedKpi;
                        };
                        $account[$stage->name] = $totalA - $totalB;
                     }else {
                        $kpi = Kpi::query()
                            ->where('stage_id', $stage->id)
                            ->where('account_id', $account->account_id)
                            ->whereYear('updated_at', $year)
                            ->whereMonth('updated_at', $month)
                            ->where('status', 0)
                            ->get()->count();
                        $failedKpi = Kpi::query()
                            ->where('stage_id', $stage->id)
                            ->where('account_id', $account->account_id)
                            ->whereYear('updated_at', $year)
                            ->whereMonth('updated_at', $month)
                            ->where('status', 1)->get()
                            ->count();
                        $account[$stage->name] = $kpi - $failedKpi;
                    }
                }
                unset($account['account_id']);
            }

        return response()->json($accounts);
    }
}
