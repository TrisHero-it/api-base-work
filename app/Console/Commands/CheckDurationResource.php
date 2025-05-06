<?php

namespace App\Console\Commands;

use App\Models\AccountResource;
use App\Models\Notification;
use App\Models\Resource;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckDurationResource extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-duration-resource';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $resources = Resource::query()
            ->where('expired_date', '!=', null)
            ->where('remind', false)->get();
        foreach ($resources as $resource) {
            $subHours = $resource->notification_before_hours ?? 0;
            $subDays = $resource->notification_before_days ?? 0;
            $expired_date = Carbon::parse($resource->expired_date)
                ->subHours($subHours)
                ->subDays($subDays);
            $now = Carbon::now();
            if ($now->greaterThan($expired_date)) {
                $accounts = AccountResource::query()->where('resource_id', $resource->id)->get();
                $notifications = [];
                foreach ($accounts as $account) {
                    $notifications[] = [
                        'account_id' => $account->account_id,
                        'manager_id' => $account->account_id,
                        'message' => 'Hạn sử dụng tài nguyên <strong>' . $resource->name . '</strong> sắp hết hạn',
                        'title' => 'Hết hạn tài nguyên',
                        'created_at' => now(),
                        'updated_at' => now(),
                        'link' => "https://work.1997.pro.vn/resources"
                    ];
                }
                Notification::insert($notifications);
                $resource->update([
                    'remind' => true
                ]);
            }
        }
    }
}
