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
                        $tasks = Task::query()->whereHas('tags', function($query) use ($arrTag) {
                            $query->whereIn('stickers.id', $arrTag);
                        })->get();
                        $arrStage = [];
                        $arrFailedStage = [];
                        foreach ($tasks as $task) {
                            $kpi = Kpi::query()
                                ->where('stage_id', $stage->id)
                                ->where('account_id', $account->account_id)
                                ->whereYear('updated_at', $year)
                                ->whereMonth('updated_at', $month)
                                ->where('status', 0)
                                ->where('task_id', $task->id)
                                ->first();
                            if ($kpi != null) {
                                $kpi['failed'] = false;
                                $kpi['created_at'] = $task->value('created_at');
                                $kpi['stage'] = $task->stage->name;
                                $kpi['task_name'] = $task->value('name');
                                $kpi['task_id'] = $task->value('id');
                                $arrStage[] = $kpi;
                            }
                            $failedKpi = Kpi::query()
                                ->where('stage_id', $stage->id)
                                ->where('account_id', $account->account_id)
                                ->whereYear('updated_at', $year)
                                ->whereMonth('updated_at', $month)
                                ->where('status', 1)
                                ->where('task_id', $task->id)
                                ->first();
                            if ($failedKpi != null) {
                                $failedKpi['failed'] = false;
                                $failedKpi['task_name'] = Task::query()->where('id', $failedKpi->task_id)->value('name');
                                $arrFailedStage[] = $failedKpi;
                            }
                        };
                        $account[$stage->name] =array_merge($arrStage, $arrFailedStage);
                     }else {
                        $kpis = Kpi::query()
                            ->where('stage_id', $stage->id)
                            ->where('account_id', $account->account_id)
                            ->whereYear('updated_at', $year)
                            ->whereMonth('updated_at', $month)
                            ->where('status', 0)
                            ->get();
                        foreach ($kpis as $kpi) {
                            $task = Task::query()->where('id', $kpi->task_id);
                            $kpi['failed'] = false;
                            $kpi['task_name'] = $task->value('name');
                            $kpi['stage'] = $task->first()->stage ? Task::query()
                                ->where('id', $kpi->task_id)
                                ->first()->stage->name : null ;
                            $kpi['task_id'] = $task->value('id');
                        }
                        $kpis = $kpis->toArray();
                        $failedKpis = Kpi::query()
                            ->where('stage_id', $stage->id)
                            ->where('account_id', $account->account_id)
                            ->whereYear('updated_at', $year)
                            ->whereMonth('updated_at', $month)
                            ->where('status', 1)->get();
                        foreach ($failedKpis as $failedKpi) {
                            $failedKpi['failed'] = false;
                            $failedKpi['task_name'] = Task::query()->where('id', $failedKpi->task_id)->value('name');
                            $failedKpi['task_id'] = Task::query()->where('id', $failedKpi->task_id)->value('id');
                        }
                        $failedKpis = $failedKpis->toArray();
                        $account[$stage->name] = array_merge($kpis, $failedKpis);
                    }
                }
                $account['Việc đơn'] = Task::query()
                    ->where('account_id', $account->account_id)
                    ->where('stage_id', null)
                    ->where('status', 'completed')
                    ->get();
                unset($account['account_id']);
            }

        return response()->json($accounts);
    }
}
