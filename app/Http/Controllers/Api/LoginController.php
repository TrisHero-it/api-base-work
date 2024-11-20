<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function store(Request $request)
    {
        try {
            if (Auth::attempt(['email'=>$request->email, 'password'=>$request->password], true)) {
                return response()->json(['token' => Auth::user()->remember_token]);
            }else {
                return response()->json(['error'=> 'Tài khoản hoặc mật khẩu không đúng'],401);
            }
        } catch (\Throwable $th) {
            return response()->json(['error'=> 'Đã xảy ra lỗi'],500);
        }
    }

}

