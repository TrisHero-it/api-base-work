<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
        public function index(Request $request)
        {
            $a = explode(' ', $request->header('Authorization'));
            $token = $a[1];
            $account = Account::where('remember_token', $token)
                ->first();
            $notifications = Notification::query()
                ->orderBy('id','desc')
                ->where('account_id', $account->id)
                ->get();

            return response()->json($notifications);
        }


}
