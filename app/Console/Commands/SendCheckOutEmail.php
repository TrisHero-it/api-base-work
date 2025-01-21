<?php

namespace App\Console\Commands;

use App\Jobs\SendEmail;
use App\Models\Account;
use Illuminate\Console\Command;

class SendCheckOutEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-check-out-email';

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
        $accounts = Account::whereNotIn('email', [
            'nguyengiaphuc2104@gmail.com',
            'phananhdeptrai1403@gmail.com',
            'ducthinh01121997@gmail.com',
            'ducnhat@admin.com',
            'nghia@gmail.com',
            'manh',
            'cinren16@gmail.com',
            'admin',
        ])->get();

        foreach ($accounts as $account) {
            SendEmail::dispatch($account->email, 'Vui lòng check bảng chấm công trong tuần vừa rồi !! ');
        }
    }
}
