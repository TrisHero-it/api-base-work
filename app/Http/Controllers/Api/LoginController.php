<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginStoreRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function store(LoginStoreRequest $request)
    {
            if (Auth::attempt(['email'=>$request->email, 'password'=>$request->password], true)) {
                return response()->json(['token' => Auth::user()->remember_token]);
            }else {
                return response()->json(['errors'=> 'Tài khoản hoặc mật khẩu không đúng'],401);
            }

    }

}

