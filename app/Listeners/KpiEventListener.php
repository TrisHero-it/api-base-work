<?php

namespace App\Listeners;

use App\Events\KpiEvent;
use App\Models\Kpi;
use App\Models\ReportDaily;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class KpiEventListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(KpiEvent $event): void
    {
        $data = $event->data;
        $kpi = Kpi::query()->create([
            'account_id' => $data['account_id'],
            'stage_id' => $data['stage_id'],
            'task_id' => $data['task_id'],
            'status' => 0,
            'total_time' => $data['total_time'],
        ]);
    }
}
