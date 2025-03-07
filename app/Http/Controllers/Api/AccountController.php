<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AccountStoreRequest;
use App\Http\Requests\AccountUpdateRequest;
use App\Models\Account;
use App\Models\Attendance;
use App\Models\DateHoliday;
use App\Models\Education;
use App\Models\FamilyMember;
use App\Models\Propose;
use App\Models\ProposeCategory;
use App\Models\View;
use App\Models\WorkHistory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class   AccountController extends Controller
{
    public function register(AccountStoreRequest $request)
    {
        $email = $request->safe()->email;
        $account = Account::create([
            'email' => $email,
            'password' => Hash::make($request->safe()->password),
            'username' => $request->username,
            'full_name' => $request->full_name,
            'day_off' => 0
        ]);

        return response()->json($account);
    }

    public function update(int $id, AccountUpdateRequest $request)
    {
        $account = Account::query()->findOrFail($id);

        if (Auth::user()->isSeniorAdmin()) {
            $data = $request->except('password', 'avatar');
            if ($request->filled('password')) {
                $data['password'] = Hash::make($request->password);
            }
            if ($request->filled('avatar')) {
                $data['avatar'] = $request->avatar;
            }
            $account->update($data);
            return response()->json($account);
        }
        //  Nếu không phải là admin thì cập nhập sẽ thành yêu cầu sửa thông tin
        $oldData = Account::select($request->keys())->where('id', $id)->get()->toArray();
        if ($request->filled('education')) {
            $education = Education::where('account_id', $id)->get();
            $oldData['education'] = $education;
        }

        if ($request->filled('work_history')) {
            $workHistory = WorkHistory::where('account_id', $id)->get();
            $oldData['work_history'] = $workHistory;
        }

        if ($request->filled('family_member')) {
            $familyMember = FamilyMember::where('account_id', $id)->get();
            $oldData['family_member'] = $familyMember;
        }

        $this->requestUpdateProfile($oldData, $request->all());
        return response()->json($account);
    }

    public function index(Request $request)
    {
        // Lấy tên từ username đẩy lên
        $name = $request->username;
        // Nếu truyền lên category_id thì láy ra những account nằm trong category đó

        if (isset($request->include)) {
            if ($request->include == 'profile') {
                $accounts = Account::with(['jobPosition', 'department', 'educations', 'familyMembers']);
            } else if ($request->include == 'list') {
                if ($request->filled('view_id')) {
                    $view = View::findOrFail($request->view_id);
                    $fields = $view->field_name;
                    $accounts = Account::select($fields)
                        ->with('department');
                } else {
                    $accounts = Account::select(
                        'id',
                        'username',
                        'full_name',
                        'avatar',
                        'role_id',
                        'email',
                        'phone',
                        'day_off',
                        'position',
                        'status',
                        'gender',
                        'birthday',
                        'contract_file',
                        'start_work_date',
                        'personal_documents',
                        'quit_work'
                    )
                        ->with('department');
                }
            }
        } else {
            $accounts = Account::select(
                'id',
                'username',
                'full_name',
                'avatar',
                'role_id',
                'email',
                'phone',
                'day_off',
                'position',
                'quit_work'
            );
        }
        $accounts = $accounts->where('username', 'like', "%$name%");
        $accounts = $accounts->when($request->filled('role_id'), function ($query) use ($request) {
            return $query->where('role_id', $request->role_id)
                ->where('quit_work', false);
        })
            ->when($request->filled('quit_work'), function ($query) use ($request) {
                return $query->where('quit_work', $request->quit_work);
            })
            ->get();
        if (isset($request->date)) {
            $b = explode('-', $request->date);
            $month2 = $b[1];
            $year2 = $b[0];
        } else {
            $month2 = now()->month;
            $year2 = now()->year;
        }
        $proposes = Propose::where('status', 'approved')
            ->whereIn('name', ['Nghỉ có hưởng lương', 'Đăng ký OT'])
            ->whereMonth('created_at', $month2 ?? now()->month)
            ->whereYear('created_at', $year2 ?? now()->year)
            ->get();
        // Lấy ra tất cả các ngày xin nghỉ
        $arrIdHoliday = $proposes->where('name', 'Nghỉ có hưởng lương')->pluck('id');
        $arrIdOverTime = $proposes->where('name', 'Đăng ký OT')->pluck('id');
        $holidays = DateHoliday::whereIn('propose_id', $arrIdHoliday)
            ->get();
        $overTime = DateHoliday::whereIn('propose_id', $arrIdOverTime)
            ->get();

        $attendances = Attendance::whereMonth('checkin', $month2)
            ->whereYear('checkin', $year2)
            ->get();

        foreach ($accounts as $account) {
            if ($account->quit_work == true) {
                $account['role'] = 'Vô hiệu hoá';
            } else {
                if ($account->role_id == 2) {
                    $account['role'] = 'Quản trị';
                } else if ($account->role_id == 3) {
                    $account['role'] = 'Quản trị cấp cao';
                } else {
                    $account['role'] = 'Thành viên thông thường';
                }
            }
            $a = 0;
            $hoursOT = 0;
            $accountHoliday = $proposes->where('account_id', $account->id)
                ->where('name', 'Nghỉ có hưởng lương')
                ->pluck('id');
            $accountHoliday = array_values($holidays->whereIn('propose_id', $accountHoliday)->toArray());
            foreach ($accountHoliday as $item) {
                $a += $item['number_of_days'];
            }
            $accountOverTime = $proposes->where('account_id', $account->id)
                ->where('name', 'Đăng ký OT')
                ->pluck('id');
            $accountOverTime = array_values($overTime->whereIn('propose_id', $accountOverTime)->toArray());
            foreach ($accountOverTime as $item) {
                $hoursOT += Carbon::parse($item['end_date'])->floatDiffInHours(Carbon::parse($item['start_date']));
            }
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
            $account['hours_over_time'] = number_format($hoursOT, 2);
            if ($account->avatar != null) {
                $account['avatar'] = $account->avatar;
            }
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
        if ($request->include == 'profile') {
            $account = Account::with(['educations', 'workHistories', 'familyMembers', 'jobPosition'])->where('id', Auth::id())->first();
        } else if ($request->include == 'my-job') {
            $account = Account::with(['jobPosition.salary'])->where('id', Auth::id())->first();
        } else {
            $account = Account::select('id', 'username', 'full_name', 'avatar', 'role_id', 'email', 'phone', 'day_off')->where('id', Auth::id())->first();
        }
        if ($account->role_id == 2) {
            $account['role'] = 'Quản trị';
        } else if ($account->role_id == 3) {
            $account['role'] = 'Quản trị cấp cao';
        } else {
            $account['role'] = 'Thành viên thông thường';
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
        $account['avatar'] = $account->avatar;

        return response()->json($account);
    }

    public function updateFiles(Request $request)
    {
        $account = Account::query()->findOrFail($request->id);
        if ($request->hasFile('files')) {
            $file = $request->file('files');
            $filename = now()->format('Y-m-d') . '_' . $file->getClientOriginalName(); // Ngày + Tên gốc
            $path = $file->storeAs('/public/files', $filename); // Lưu file với tên mới
            $imageUrl = Storage::url($path);

            $account->update([
                'files' => $imageUrl
            ]);
        }

        return response()->json($account);
    }

    private function requestUpdateProfile(array $oldData, array $newData)
    {
        $category = ProposeCategory::where('name', 'Cập nhật thông tin cá nhân')->first();
        $data = [
            'name' => 'Cập nhật thông tin cá nhân',
            'propose_category_id' => $category->id,
            'old_value' => json_encode($oldData),
            'new_value' => json_encode($newData),
            'account_id' => Auth::user()->id,
        ];
        $propose = Propose::create($data);

        return response()->json($propose);
    }

    public function accountsField()
    {
        $data = [];
        $fields =  Schema::getColumnListing('accounts');
        $salary = Schema::getColumnListing('salaries');
        $contract = Schema::getColumnListing('contracts');

        $data['person_info'] = $fields;
        $data['salary'] = $salary;
        $data['contract'] = $contract;

        return response()->json($data);
    }

    public function deleteToken(Request $request)
    {
        $account = Account::query()->findOrFail($request->id);
        $account->tokens()->delete();

        return response()->json([
            'message' => 'Đăng xuất thành công'
        ]);
    }
}
