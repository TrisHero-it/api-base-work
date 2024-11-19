<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
        public function index()
        {
            $notifications = Notification::query()->get();

            return response()->json($notifications);
        }


}
