<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AccountStoreRequest;
use App\Http\Requests\AccountUpdateRequest;
use App\Models\Account;
use App\Models\AccountWorkflowCategory;
use App\Models\Department;
use Illuminate\Http\Request;
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
            $data = $request->validated()
                ->except('avatar');
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
            $departments = Department::query()->get();
            foreach ($departments as $department) {
                $department['type'] = 'department';
                $department['username'] = $department->id;
                $department['full_name'] = $department->name;
            }
            $accounts = array_merge($departments->toArray(), $accounts);

        return response()->json($accounts);
    }

    public function show(int $id) {
            $account = Account::query()->where('id', $id)->first();

            return response()->json($account);
    }

    public function myAccount(Request $request) {
        $token = $request->header('Authorization');
        if ($token==null) {
            return response()->json([
                'error' => 'Bạn chưa đăng nhập'
            ]);
        }
        $token = explode(' ', $token);
        $token = $token[1];
        $account = Account::query()->where('remember_token', $token)->first();
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

}
