<?php

namespace App\Listeners;

use App\Events\HistoryMoveTaskEvent;
use App\Models\HistoryMoveTask;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class HistoryMoveTaskListener
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
    public function handle(HistoryMoveTaskEvent $event): void
    {
        $data = $event->data;
        HistoryMoveTask::query()->create([
            'account_id' => $data['account_id'],
            'task_id' => $data['task_id'],
            'old_stage' => $data['old_stage'],
            'new_stage' => $data['new_stage'],
            'started_at' => $data['started_at'] ?? null,
            'worker' => $data['worker'],
            'expired_at'=> $data['expired_at'] ?? null,
        ]);
    }
}
