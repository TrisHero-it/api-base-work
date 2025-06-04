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
        $account = Account::when($request->filled('email'), function ($query) use ($request) {
            return $query->where('email', $request->email)->orWhere('username', $request->email);
        })
            ->first();

        if (!$account) {
            return response()->json([
                'message' => 'Email hoặc tên tài khoản không tồn tại',
                'errors' => [
                    'email' => 'Email hoặc tên tài khoản không tồn tại',
                ],
            ], 400);
        }

        if ($account->quit_work == true) {
            return response()->json([
                'message' => 'Tài khoản đã bị vô hiệu hoá',
                'errors' => [
                    'email' => 'Tài khoản đã bị vô hiệu hoá',
                ],
            ], 400);
        }

        // if ($account->attendance_at_home != true && explode(',', $request->header('X-Forwarded-For'))[0] != '1.54.23.46') {
        //     return response()->json([
        //         'message' => 'Ip mạng không được cho phép',
        //         'errors' => [
        //             'email' => 'Ip mạng không được cho phép',
        //         ],
        //     ], 400);
        // }

        $credentials = [
            'password' => $request->password,
        ];

        if ($request->filled('email') && $request->email == $account->email) {
            $credentials['email'] = $request->email;
        } else {
            $credentials['username'] = $request->email;
        }

        if (Auth::attempt($credentials)) {
            $token = $account->createToken('auth_token')->plainTextToken;

            $loginHistory = new LoginHistoryController();
            $loginHistory->store($request);

            return response()->json([
                'message' => 'Đăng nhập thành công',
                'token' => $token,
            ], 200);
        } else {
            return response()->json([
                'message' => 'Đăng nhập thất bại',
                'errors' => [
                    'email' => 'Email hoặc mật khẩu không chính xác',
                ],
            ], 400);
        }
    }
}
