<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\Propose;
use App\Models\ProposeCategory;
use Illuminate\Console\Command;

class ResetWorkFormHomeOfAccount extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:reset-work-form-home-of-account';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mỗi 12h đêm load 1 lần để những tài khoản nảo thuộc dạng làm ở nhà sẽ được update cho phép điểm danh tạo nhà';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $globalBan = [
            11,
            12,
            13,
            14,
            15,
            30,
            32,
            2,
            17,
            5,
            6
        ];
        $account = Account::whereNotIn('id', $globalBan)
            ->where('attendance_at_home', true)
            ->update([
                'attendance_at_home' => false
            ]);
        $category = ProposeCategory::where('name', 'Đăng ký làm ở nhà')->first();
        $propose = Propose::where('propose_category_id', $category->id)
            ->where('status', 'approved')
            ->whereDate('date_wfh',  now())
            ->get();
        if ($propose != null) {
            Account::where('id', $propose->account_id)->update([
                'attendance_at_home' => true
            ]);
        }
    }
}
