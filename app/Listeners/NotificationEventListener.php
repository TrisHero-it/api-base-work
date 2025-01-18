<?php

namespace App\Listeners;

use App\Events\NotificationEvent;
use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotificationEventListener
{
    /**
     * Handle the event.
     */
    public function handle(NotificationEvent $event): void
    {
        $data = $event->data;
        Notification::query()->create([
            'title' => 'Nhiệm vụ mới cho bạn',
            'message' => 'Nhiệm vụ <strong>' . $data['task_name'] . '</strong> được <strong>' . $data['full_name'] . '</strong> giao cho bạn',
            'link' => 'https://work.1997.pro.vn/workflows/' . $data['workflow_id'],
            'account_id' => $data['account_id'],
            'manager_id' => $data['manager_id']
        ]);
    }
}
