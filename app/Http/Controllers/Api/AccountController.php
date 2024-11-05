<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AccountProfile;
use App\Models\AccountWorkflowCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AccountController extends Controller
{
    public function store(Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|string|email|max:255|unique:accounts', // Kiểm tra email không được trùng
                'password' => 'required|string|min:6', // Xác nhận mật khẩu
            ],
        [
            'email.unique' => 'Email đã được sử dụng',
        ]);
            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 422);
            }

            $email = $request->email;
            $result = explode('@', $email)[0];
            $account = Account::create([
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'username' => '@'.$result
            ]);
            AccountProfile::create([
                'email'=> $account->id,
                'full_name'=>$result,
            ]);
            return response()->json([
                'success' => 'Đăng ký thành công'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Đã xảy ra lỗi'
            ], 500);
        }
    }

    public function update(Request $request, int $id) {
        try {
            $account = Account::findOrFail($id);
            $accountProfile = AccountProfile::where('email', $account->email)->first();
            $accountProfile::update($request->all());
            return response()->json([
                'success' => 'Cập nhập thành kông'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Đã xảy ra lỗi'
            ], 500);
        }
    }

    public function search(Request $request) {
        $arrAccount = [];

            $name = str_replace('@', '', $request->username);

            if (isset($request->category_id)) {
                $accounts = [];
                $a = AccountWorkflowCategory::query()->where('workflow_category_id', $request->category_id)->get();
                foreach ($a as $item) {
                    $accounts[] = $item->account;
                }
            }else {
                $accounts = Account::query()->select('id', 'username', 'email')->where('username', 'like', "%$name%")->get()->toArray();
            }
            foreach ($accounts as $account) {
                $accountProfile = AccountProfile::query()->select('full_name', 'position')->where('email', $account['id'])->first()->toArray();
                $arrAccount[] = array_merge($account, $accountProfile);
            }
        return response()->json($arrAccount);
    }

    public function show(int $id, Request $request) {
            $account = Account::query()->select('id', 'username')->where('id', $id)->first();
            $detailAccount = AccountProfile::query()->where('email', $account->id)->first();
            $account = array_merge($account->toArray(), $detailAccount->toArray());
            return response()->json($account);
    }

}
