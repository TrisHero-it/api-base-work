<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SendEmail;
use Illuminate\Http\Request;

class EmailController extends Controller
{
    public function sendEmail(Request $request)
    {
        try {
            SendEmail::dispatch([
                'email' => 'trinmph36953@fpt.edu.vn',
                'body' => 'Tét'
            ]);
        }catch (\Exception $exception){
            return response()->json([
                'error' => $exception->getMessage()
            ]);
        }
    }
}
