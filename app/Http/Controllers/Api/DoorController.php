<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class DoorController extends Controller
{
    public function open()
    {
        $response = Http::timeout(3)->get('http://192.168.1.242:80');

        return response()->json([
            'success' => true,
        ], 200);
    }
}
