<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MailController extends Controller
{
    public function sendMail(Request $request)
    {
        try {
            $data['email'] = $request->email;
            $data['body'] = $request->body;
            \App\Jobs\SendEmail::dispatch($data);

            return response()->json([
                'success' => 'Đã gửi email',
            ]);
        }catch (\Exception $exception){
            return response()->json(['error'=> 'Đã xảy ra lỗi'], 500);
        }
    }
}
