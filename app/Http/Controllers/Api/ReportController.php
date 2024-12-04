<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ReportDaily;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index()
    {
        $reports = ReportDaily::query()->get();
        foreach ($reports as $report) {
            $report['task_name'] = $report->kpi->task->name;
            $report['task_id'] = $report->kpi->task->code;
            $report['stage_name'] = $report->kpi->stage->name;
            $report['total_time'] = $report->kpi->total_time;
            unset($report['kpi']);
        }

        return response()->json($reports);
    }
}
