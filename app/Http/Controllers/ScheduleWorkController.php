<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ScheduleWorkController extends Controller
{
        public function index()
        {
            // Tuần này
            $endOfThisWeek = Carbon::now()->endOfWeek()->toDateString();
            // Tuần trước
            $startOfLastWeek = Carbon::now()->startOfWeek()->toDateString();
            $accounts = Account::all();
            foreach ($accounts as $account) {
                $account['scheduleWork'] = Task::query()
                    ->where('account_id', $account->id)
                    ->whereBetween('started_at', [$startOfLastWeek, $endOfThisWeek])
                    ->orderBy('started_at', 'asc')
                    ->get();
            }

            return $accounts;
        }
}
