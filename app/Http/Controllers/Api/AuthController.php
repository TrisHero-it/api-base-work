<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use Auth;
use Hash;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function store(Request $request)
    {
        $account = Account::where('email', $request->email)->first();

        if (!$account) {
            return response()->json([
                'message' => 'Email không tồn tại',
            ], 400);
        }

        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $token = $account->createToken('auth_token')->plainTextToken;
            return response()->json([
                'message' => 'Đăng nhập thành công',
                'token' => $token,
            ], 200);
        }
    }
}
