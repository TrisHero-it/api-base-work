<?php

namespace App\Console\Commands;

use App\Models\Account;
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
        $accounts = Account::all();
        foreach ($accounts as $account) { 
            if (now()->month == 12) {
                $account->update(['day_off'=> 1]);
            }else {
                $account->update(['day_off'=> $account->day_off+1]);
            }
        }
    }
}
