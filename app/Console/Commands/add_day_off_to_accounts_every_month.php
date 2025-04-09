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
            if ($account->dayoffAccount != null) {
                if ($account->start_work_date != null) {
                    $diffDay = $now->diffInDays($account->start_work_date);
                    if ($diffDay > 30) {
                        $account->dayoffAccount->update([
                            'dayoff_count' => $account->dayoffAccount->dayoff_count + 1
                        ]);
                    }
                }
                $dayoff = $account->dayoffAccount->dayoff_count + $account->dayoffAccount->dayoff_long_time_worker;
                $account['dayoff'] = $dayoff;
            } else {
                $account['dayoff'] = 0;
            }
        }
    }
}
