<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AccountStoreRequest;
use App\Http\Requests\AccountUpdateRequest;
use App\Models\Account;
use App\Models\AccountWorkflowCategory;
use App\Models\Attendance;
use App\Models\DateHoliday;
use App\Models\Department;
use App\Models\Propose;
use App\Models\ProposeCategory;
use Carbon\Carbon;
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
            'username' => '@' . $result,
            'full_name' => $result,
        ]);

        return response()->json($account);
    }

    public function update(int $id, AccountUpdateRequest $request)
    {
        $account = Account::query()
            ->findOrFail($id);
        $data = $request->except('password', 'avatar');
        if (isset($request->password)) {
            $data['password'] = Hash::make($request->password);
        }
        if (isset($request->avatar)) {
            if ($request->hasFile('avatar')) {
                $data['avatar'] = Storage::put('public/avatars', $request->avatar);
                $data['avatar'] = env('APP_URL') . Storage::url($data['avatar']);
            } else {
                $data['avatar'] = $request->avatar;
            }
        }
        $account->update($data);

        return response()->json($account);
    }

    public function index(Request $request)
    {
        // Lấy tên từ username đẩy lên
        $name = str_replace('@', '', $request->username);
        $a = 0;
        // Nếu truyền lên category_id thì láy ra những account nằm trong category đó
        if (isset($request->category_id)) {
            $accounts = [];
            $accountWorkflowCategories = AccountWorkflowCategory::query()
                ->where('workflow_category_id', $request->category_id)
                ->get();
            foreach ($accountWorkflowCategories as $item) {
                $accounts[] = $item->account;
            }
        } else {
            $accounts = Account::query()->where('username', 'like', "%$name%")->get();
        }
        $month = now()->month;
        $year = now()->year;
        $category = ProposeCategory::where('name', 'Đăng ký nghỉ')->first();
        $proposes = Propose::where('propose_category_id', $category->id)
            ->where('account_id', Auth::id())
            ->where('status', 'approved')
            ->where('name', 'Nghỉ có hưởng lương')
            ->get()
            ->pluck('id');
        // Lấy ra tất cả các ngày xin nghỉ
        $holidays = DateHoliday::whereIn('propose_id', $proposes)
            ->whereMonth('start_date', $month)
            ->whereYear('start_date', $year)
            ->get();
        foreach ($holidays as $date) {
            $a += $date->number_of_days;
        }

        if (isset($request->date)) {
            $b = explode('-', $request->date);
            $month2 = $b[1];
            $year2 = $b[0];
        } else {
            $month2 = now()->month;
            $year2 = now()->year;
        }
        $attendances = Attendance::whereMonth('checkin', $month2)
            ->whereYear('checkin', $year2)
            ->get();
        foreach ($accounts as $account) {
            $totalWorkDay = 0;
            // Lọc từng tài khoản để tính ngày công
            $newAttendances = null;
            $newAttendances = $attendances->where('account_id', $account->id);
            foreach ($newAttendances as $newAttendance) {
                $diff = 0;
                $hours = 0;
                $workday = 0;
                $checkout = null;
                if ($newAttendance->checkout != null) {
                    $checkout = Carbon::parse($newAttendance->checkout);
                    $diff = $checkout->diffInMinutes($newAttendance->checkin);
                    $hours = $diff / 60;
                    $workday = $hours / 9;
                }
                $totalWorkDay += $workday;
            }
            $account['day_off_used'] = $a;
            $account['workday'] = $totalWorkDay == 0 ? $totalWorkDay : number_format($totalWorkDay, 3);
        }

        return response()->json($accounts);
    }

    public function show(int $id)
    {
        $account = Account::query()->where('id', $id)->first();

        return response()->json($account);
    }

    public function destroy(int $id)
    {
        $account = Account::query()->findOrFail($id);
        $account->delete();

        return response()->json([
            'message' => 'Xóa thành công'
        ]);
    }

    public function myAccount(Request $request)
    {
        $account = Auth::user();
        if ($account->role_id == 1) {
            $account['role'] = 'Admin';
        } else if ($account->role_id == 2) {
            $account['role'] = 'Admin lv2';
        } else {
            $account['role'] = 'User';
        }
        unset($account->role_id);
        $month = now()->month;
        $year = now()->year;
        $category = ProposeCategory::where('name', 'Đăng ký nghỉ')->first();
        $proposes = Propose::where('propose_category_id', $category->id)
            ->where('status', 'approved')
            ->where('account_id', $account->id)
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->get()
            ->pluck('id');
        // Lấy ra tất cả các ngày xin nghỉ
        $holidays = DateHoliday::whereIn('propose_id', $proposes)
            ->get();
        $a = 0;
        foreach ($holidays as $date) {
            $a += $date->number_of_days;
        }
        $account['day_off_used'] = $a;

        return response()->json($account);
    }

    public function forgotPassword(Request $request)
    {
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
