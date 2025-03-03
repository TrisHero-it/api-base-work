<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginStoreRequest;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function store(LoginStoreRequest $request)
    {
        $account = Account::where('email', $request->email)->first();

        if (!$account) {
            return response()->json([
                'message' => 'Email không tồn tại',
                'errors' => [
                    'email' => 'Email không tồn tại',
                ],
            ], 400);
        }

        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $token = $account->createToken('auth_token')->plainTextToken;
            return response()->json([
                'message' => 'Đăng nhập thành công',
                'token' => $token,
            ], 200);
        }else {
            return response()->json([
                'message' => 'Đăng nhập thất bại',
                'errors' => [
                    'email' => 'Email hoặc mật khẩu không chính xác',
                ],
            ], 400);
        }
    }

}

