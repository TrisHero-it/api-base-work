<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AccountStoreRequest;
use App\Http\Requests\AccountUpdateRequest;
use App\Models\Account;
use App\Models\AccountWorkflowCategory;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class AccountController extends Controller
{
    public function store(AccountStoreRequest $request)
    {
            $email = $request->safe()->email;
            $result = explode('@', $email)[0];
            $account = Account::create([
                'email' => $request->safe()->email,
                'password' => Hash::make($request->safe()->password),
                'username' => '@'.$result,
                'full_name'=>$result,
            ]);

            return response()->json($account);
    }

    public function update(int $id, AccountUpdateRequest $request) {
            $account = Account::query()
                ->findOrFail($id);
            $data = $request->except('password', 'avatar');
            if (isset($request->password)) {
                $data['password'] = Hash::make($request->password);
            }
            if (isset($request->avatar)) {
                $data['avatar'] = Storage::put('public/avatars', $request->avatar);
                $data['avatar'] = env('APP_URL').Storage::url($data['avatar']);
            }
            $account->update($data);

            return response()->json($account);
    }

    public function index(Request $request) {
        // Lấy tên từ username đẩy lên
            $name = str_replace('@', '', $request->username);
        // Nếu truyền lên category_id thì láy ra những account nằm trong category đó
            if (isset($request->category_id)) {
                $accounts = [];
                $accountWorkflowCategories = AccountWorkflowCategory::query()
                    ->where('workflow_category_id', $request->category_id)
                    ->get();
                foreach ($accountWorkflowCategories as $item) {
                    $accounts[] = $item->account;
                }
            }else {
                $accounts = Account::query()->where('username', 'like', "%$name%")->get()->toArray();
            }

        return response()->json($accounts);
    }

    public function show(int $id) {
            $account = Account::query()->where('id', $id)->first();

            return response()->json($account);
    }

    public function destroy(int $id) {
            $account = Account::query()->findOrFail($id);
            $account->delete();

            return response()->json([
                'message' => 'Xóa thành công'
            ]);
    }

    public function myAccount(Request $request) {
        $token = $request->header('Authorization');
        if ($token==null) {
            return response()->json([
                'error' => 'Bạn chưa đăng nhập'
            ]);
        }

        $account = Auth::user();
            if ($account->role_id == 1 ){
                $account['role'] = 'Admin';
            }else if ($account->role_id == 2 ){
                $account['role'] = 'Admin lv2';
            }else {
                $account['role'] = 'User';
            }
            unset($account->role_id);
        return response()->json($account);
    }

    public function forgotPassword(Request $request) {
        $email = $request->email;
        $account = Account::query()->where('email', $email)->first();
        if ($account == null) {
            return response()->json([
                'error' => 'Email không tồn tại'
            ]);
        } else {
            $account->update([
                'password' => Hash::make('123456')
            ]);

            return response()->json([
                'success' => 'Mật khẩu đã được reset về 123456'
            ]);
        }
    }
}
