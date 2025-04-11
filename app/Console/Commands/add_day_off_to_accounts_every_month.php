<?php

namespace App\Console\Commands;

use App\Models\Account;
use Carbon\Carbon;
use Illuminate\Console\Command;

class add_day_off_to_accounts_every_month extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:add_day_off_to_accounts_every_month';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'mỗi tháng thêm cho tài khoản 1 ngày nghỉ phép';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $accounts = Account::with('dayoffAccount')->get();
        $now = Carbon::now();
        foreach ($accounts as $account) {
            $startWorkDate = Carbon::parse($account->start_work_date);
            $diffDay = $now->diffInDays($startWorkDate);
            if ($diffDay > 30) {
                if ($account->dayoffAccount == null) {
                    $account->dayoffAccount()->create([
                        'dayoff_count' => 0,
                        'dayoff_long_time_worker' => 1
                    ]);
                } else {
                    $account->dayoffAccount->update([
                        'dayoff_long_time_worker' => $account->dayoffAccount->dayoff_long_time_worker + 1
                    ]);
                }
            }
        }
    }
}
