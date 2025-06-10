<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AccountStoreRequest;
use App\Http\Requests\AccountUpdateRequest;
use App\Http\Resources\AccountResource;
use App\Models\Account;
use App\Models\AccountDepartment;
use App\Models\AccountWorkflow;
use App\Models\Asset;
use App\Models\DateHoliday;
use App\Models\DayoffAccount;
use App\Models\Department;
use App\Models\Education;
use App\Models\FamilyMember;
use App\Models\JobPosition;
use App\Models\Propose;
use App\Models\ProposeCategory;
use App\Models\Role;
use App\Models\Salary;
use App\Models\Task;
use App\Models\View;
use App\Models\WorkHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class AccountController extends Controller
{
    public function register(AccountStoreRequest $request)
    {
        $email = $request->safe()->email;
        $username = $request->safe()->username ?? explode('@', $email)[0];
        $account = Account::create([
            'email' => $email,
            'password' => Hash::make($request->safe()->password),
            'username' => $username,
            'full_name' => $request->full_name ?? $username,
        ]);

        return response()->json($account);
    }

    public function update(int $id, AccountUpdateRequest $request)
    {
        $account = Account::query()->with(['dayoffAccount', 'jobPositionActive.salary'])->findOrFail($id);
        if ($request->filled('avatar')) {
            $account->update([
                'avatar' => $request->avatar
            ]);
            return response()->json([
                'message' => 'Cập nhật avatar thành công',
                'avatar' => $request->avatar
            ]);
        }

        if ($request->filled('new_password')) {
            $change = $this->changePassword($request, $account);
            if ($change == true) {
                return response()->json([
                    'message' => 'đổi mk thành công'
                ]);
            } else {
                return response()->json([
                    'message' => 'Đã xảy ra lỗi',
                    'error' => 'Đã xảy ra lỗi'
                ]);
            }
        }
        $models = [
            'education' => Education::class,
            'work_history' => WorkHistory::class,
            'family_member' => FamilyMember::class,
            'job_position' => JobPosition::class,
        ];
        if (Auth::user()->isSeniorAdmin()) {
            $data = $request->except('password', 'avatar', 'position', 'department_name', 'personal_documents', 'day_off', 'staff_type');
            foreach ($models as $key => $model) {
                $arr = [];
                if ($request->filled($key)) {
                    if (isset($request->$key['id']) && $key != 'job_position') {
                        $newValue = collect($request->input($key))->except('gross_salary', 'net_salary', 'department_name')->toArray();
                        $model::where('id', $request->$key['id'])->update($newValue);
                    } else {
                        if ($key == 'job_position') {
                            $model::where('status', 'active')
                                ->where('account_id', $id)
                                ->update(['status' => 'inactive']);
                        }
                        $arr = array_merge(['account_id' => $id], $request->$key);
                        $model::create($arr);
                    }
                }
            }
            if (isset($request->department_name)) {
                $department = Department::where('name', $request->department_name)->first();
                AccountDepartment::where('account_id', $id)->update(['department_id' => $department->id]);
            }
            if (isset($request->personal_documents)) {
                $newPersonalDocuments = $account->personal_documents;
                foreach ($request->personal_documents as $personalDocument) {
                    $newPersonalDocuments[] = $personalDocument;
                }
                $data['personal_documents'] = $newPersonalDocuments;
            }
            $account->update($data);
            if ($request->filled('dayoff_account')) {
                if ($account->dayoffAccount != null) {
                    $date = isset($request->dayoff_account['effective_date']) ? $request->dayoff_account['effective_date'] : now();
                    $account->dayoffAccount->update([
                        'effective_date' => $date,
                        'total_holiday_with_salary' => $request->dayoff_account['total_holiday_with_salary'],
                        'seniority_holiday' => $request->dayoff_account['seniority_holiday']
                    ]);
                } else {
                    DayoffAccount::create([
                        'account_id' => $id,
                        'effective_date' => $request->dayoff_account['effective_date'] ?? now(),
                        'total_holiday_with_salary' => $request->dayoff_account['total_holiday_with_salary'],
                        'seniority_holiday' => $request->dayoff_account['seniority_holiday']
                    ]);
                }
            }

            if ($request->filled('position')) {
                $jobPosition = JobPosition::where('status', 'active')
                    ->where('account_id', $id)
                    ->where('name', '!=', $request->position)
                    ->first();
                $name = null;
                if (isset($jobPosition)) {
                    $name = $jobPosition->name;
                    $jobPosition->update([
                        'status' => 'inactive'
                    ]);
                    $salary = Salary::where('job_position_id', $jobPosition->id)->first();
                }
                $jobPosition2 = JobPosition::create([
                    'account_id' => $id,
                    'name' => $request->position,
                    'old_postion' => $name,
                    'status' => 'active',
                ]);

                if ($request->filled('salary')) {
                    $travelAllowance = $request->salary['travel_allowance'] ?? 0;
                    $eatAllowance = $request->salary['eat_allowance'] ?? 0;
                    $kpi = $request->salary['kpi'] ?? 0;
                    $basicSalary = $request->salary['basic_salary'] ?? 0;
                } else {
                    $travelAllowance = $salary->travel_allowance ?? 0;
                    $eatAllowance = $salary->eat_allowance ?? 0;
                    $kpi = $salary->kpi ?? 0;
                    $basicSalary = $salary->basic_salary ?? 0;
                }

                Salary::create([
                    'job_position_id' => isset($jobPosition2) ? $jobPosition2->id : $jobPosition->id,
                    'travel_allowance' => $travelAllowance,
                    'eat_allowance' => $eatAllowance,
                    'kpi' => $kpi,
                    'basic_salary' => $basicSalary,
                ]);
            }
            if ($request->filled('salary')) {
                $travelAllowance = $request->salary['travel_allowance'] ?? 0;
                $eatAllowance = $request->salary['eat_allowance'] ?? 0;
                $kpi = $request->salary['kpi'] ?? 0;
                $basicSalary = $request->salary['basic_salary'] ?? 0;
            } else {
                $travelAllowance = $salary->travel_allowance ?? 0;
                $eatAllowance = $salary->eat_allowance ?? 0;
                $kpi = $salary->kpi ?? 0;
                $basicSalary = $salary->basic_salary ?? 0;
            }
            if (isset($request->salary['id'])) {
                $account->jobPositionActive->salary->update([
                    'travel_allowance' => $travelAllowance,
                    'eat_allowance' => $eatAllowance,
                    'kpi' => $kpi,
                    'basic_salary' => $basicSalary,
                ]);
            }

            return response()->json($account);
        }

        //  Nếu không phải là admin thì cập nhập sẽ thành yêu cầu sửa thông tin
        $arrKeys = array_keys($request->except(
            'password',
            'avatar',
            'position',
            'department_name',
            'email',
            'username',
            'education',
            'history_works',
            'family_member',
            'role',
            'department',
            'job_position',
            'dayoff_account',
            'salary'
        ));
        $oldData = [];
        if ($arrKeys != null) {
            $oldData = Account::select($arrKeys)
                ->find($id);
        }
        foreach ($models as $key => $model) {
            if ($request->filled($key)) {
                $oldData[$key] = $model::where('account_id', $id)
                    ->get();
            }
        }

        if ($request->filled('salary')) {
            $jobPosition = JobPosition::where('status', 'active')
                ->where('account_id', $id)
                ->first();
            if (isset($jobPosition)) {
                $salary = Salary::where('job_position_id', $jobPosition->id)->first();
                $oldData['salary'] = $salary;
            }
        }

        if (!is_array($oldData)) {
            $oldData = $oldData->toArray();
        }

        $this->requestUpdateProfile($oldData, $request->all());

        return response()->json($account);
    }

    public function index(Request $request)
    {
        $name = $request->search;
        if (isset($request->include)) {
            $accounts = Account::with(['jobPosition.salary', 'department', 'workHistories', 'educations', 'familyMembers', 'dayoffAccount', 'contracts.category', 'contractActive']);
        } else {
            $accounts = Account::select(
                'id',
                'username',
                'full_name',
                'avatar',
                'role_id',
                'email',
                'phone',
                'quit_work'
            )->with('dayoffAccount');
        }
        if ($name != null) {
            $accounts = $accounts->where('full_name', 'like', "%$name%");
        }
        if ($request->filled('role_id')) {
            $accounts = $accounts->where('role_id', $request->role_id)
                ->where('quit_work', false);
        }
        if ($request->filled('quit_work')) {
            $accounts = $accounts->where('quit_work', $request->quit_work);
        }

        $departments = AccountDepartment::query()->get();

        $roles = Role::query()->get();

        $accounts = $accounts->get();
        foreach ($accounts as $account) {
            if ($departments->where('account_id', $account->id)->first() != null) {
                $account->department_id = $departments->where('account_id', $account->id)->first()->department_id;
            } else {
                $account->department_id = null;
            }
            if ($account->start_work_date != null) {
                $mocThoiGian = Carbon::parse($account->start_work_date); // mốc thời gian
                $hienTai = Carbon::now();
                $thoiGianLamViec = $mocThoiGian->diff($hienTai);
                $account->seniority = $thoiGianLamViec->y . ' năm ' . $thoiGianLamViec->m . ' tháng ' . $thoiGianLamViec->d . ' ngày';
            }
            if ($account->contractActive != null) {
                $account->name_contract = $account->contractActive['files'][0]['file_name'];
                $account->category__contract_id = $account->contractActive->category->name;
                $account->url_contract = $account->contractActive['files'][0]['file_url'];
                unset($account->contractActive);
            }
            if ($account->dayoffAccount != null) {
                $account->day_off = $account->dayoffAccount->dayoff_count + $account->dayoffAccount->dayoff_long_time_worker;
                $account->paid_day_off = $account->dayoffAccount->dayoff_count;
                $account->dayoff_long_time_worker = $account->dayoffAccount->dayoff_long_time_worker;
            }
            if ($account->jobPosition->where('status', 'active')->first() != null) {
                if (!empty($account->jobPosition)) {
                    $account->kpi = $account->jobPosition->where('status', 'active')->first()->salary->kpi;
                    $account->basic_salary = $account->jobPosition->where('status', 'active')->first()->salary->basic_salary;
                    $account->travel_allowance = $account->jobPosition->where('status', 'active')->first()->salary->travel_allowance;
                    $account->eat_allowance = $account->jobPosition->where('status', 'active')->first()->salary->eat_allowance;
                } else {
                    $account->kpi = 0;
                    $account->basic_salary = 0;
                    $account->travel_allowance = 0;
                    $account->eat_allowance = 0;
                }
                $account->position = $account->jobPosition->where('status', 'active')->first()->name;
            } else {
                $account->position = null;
            }
            if (!empty($account->department->toArray())) {
                $account->department_name = $account->department[0]->name;
                unset($account->department);
            }
            if ($account->quit_work == true) {
                $account['role'] = 'Vô hiệu hoá';
            } else {
                if ($account->role_id == 2) {
                    $account['role'] = $roles->where('id', 2)->first()->name;
                } else if ($account->role_id == 3) {
                    $account['role'] = $roles->where('id', 3)->first()->name;
                } else {
                    $account['role'] = $roles->where('id', 1)->first()->name;
                }
            }
            $account->avatar = env('APP_URL') . $account->avatar;
        }

        return response()->json($accounts);
    }

    public function show(int $id, Request $request)
    {
        if ($request->include == 'profile') {
            $account = Account::with(['educations', 'workHistories', 'familyMembers', 'dayoffAccount', 'jobPosition' => function ($query) {
                $query->with('salary') // Vẫn lấy đầy đủ thông tin từ salary
                    ->addSelect('job_positions.*')
                    ->leftJoin('salaries', 'job_positions.id', '=', 'salaries.job_position_id')
                    ->addSelect(DB::raw('(salaries.basic_salary + salaries.travel_allowance + salaries.eat_allowance + salaries.kpi) as total_salary'));
            }, 'contracts.category', 'department'])
                ->where('id', $id)
                ->first();
            if ($account->jobPosition != null) {
                if ($account->jobPosition->where('status', 'active')->first() != null) {
                    $salary = Salary::where('job_position_id', $account->jobPosition->where('status', 'active')->first()->id)->first();
                    $account->salary = $salary;
                    $account->position = $account->jobPosition->where('status', 'active')->first()->name;
                }
            }
            $account->employee_type = $account->staff_type;
            if ($account->department != null && isset($account->department[0])) {
                $account->department_name = $account->department[0]->name;
            }
            if ($account->dayoffAccount != null) {
                $account->day_off = $account->dayoffAccount->dayoff_count + $account->dayoffAccount->dayoff_long_time_worker;
                $account->paid_day_off = $account->dayoffAccount->dayoff_count;
                $account->dayoff_long_time_worker = $account->dayoffAccount->dayoff_long_time_worker;
            }

            unset($account->department);
        } else if ($request->include == 'my-job') {
            $account = Account::with(['jobPosition' => function ($query) {
                $query->with('salary') // Vẫn lấy đầy đủ thông tin từ salary
                    ->addSelect('job_positions.*')
                    ->leftJoin('salaries', 'job_positions.id', '=', 'salaries.job_position_id')
                    ->addSelect(DB::raw('(salaries.basic_salary + salaries.travel_allowance + salaries.eat_allowance + salaries.kpi as total_salary'));
            }])->where('id', $id)->first();
            $salary = Salary::where('job_position_id', $account->jobPosition->where('status', 'active')->first()->id)->first();
            $account->salary = $salary;
        } else {
            $account = Account::select('id', 'username', 'full_name', 'avatar', 'role_id', 'email', 'phone')
                ->findOrFail($id);
        }
        $roles = Role::query()->get();
        if ($account->role_id == 2) {
            $account['role'] = $roles->where('id', 2)->first()->name;
        } else if ($account->role_id == 3) {
            $account['role'] = $roles->where('id', 3)->first()->name;
        } else {
            $account['role'] = $roles->where('id', 1)->first()->name;
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
            $account = Account::with(['educations', 'workHistories', 'dayoffAccount', 'familyMembers', 'jobPosition' => function ($query) {
                $query->with('salary') // Vẫn lấy đầy đủ thông tin từ salary
                    ->addSelect('job_positions.*')
                    ->leftJoin('salaries', 'job_positions.id', '=', 'salaries.job_position_id')
                    ->addSelect(DB::raw('(salaries.basic_salary + salaries.travel_allowance + salaries.eat_allowance + salaries.kpi) as total_salary'));
            }, 'contracts.category', 'department'])
                ->where('id', Auth::id())
                ->first();
            $account->salary = null;
            if ($account->jobPosition->where('status', 'active')->first() != null) {
                $salary = Salary::where('job_position_id', $account->jobPosition->where('status', 'active')->first()->id)->first();
                $account->salary = $salary;
                $account->position = $account->jobPosition->where('status', 'active')->first()->name;
            }
            if ($account->department_id != null) {
                $account->department_name = $account->department[0]->name;
            }
            unset($account->department);
        } else if ($request->include == 'my-job') {
            $account = Account::with(['dayoffAccount', 'jobPosition' => function ($query) {
                $query->with('salary') // Vẫn lấy đầy đủ thông tin từ salary
                    ->addSelect('job_positions.*')
                    ->leftJoin('salaries', 'job_positions.id', '=', 'salaries.job_position_id')
                    ->addSelect(DB::raw('(salaries.basic_salary + salaries.travel_allowance + salaries.eat_allowance + salaries.kpi as total_salary'));
            }])->where('id', Auth::id())->first();
            $salary = Salary::where('job_position_id', $account->jobPosition->where('status', 'active')->first()->id)->first();
            $account->salary = $salary;
        } else {
            $account = Account::select('id', 'username', 'full_name', 'avatar', 'role_id', 'email', 'phone', 'work_from_home')
                ->with('dayoffAccount')
                ->where('id', Auth::id())
                ->first();
        }
        $roles = Role::query()->get();
        if ($account->role_id == 2) {
            $account['role'] = $roles->where('id', 2)->first()->name;
        } else if ($account->role_id == 3) {
            $account['role'] = $roles->where('id', 3)->first()->name;
        } else {
            $account['role'] = $roles->where('id', 1)->first()->name;
        }
        unset($account->role_id);
        $month = now()->month;
        $year = now()->year;
        $category = ProposeCategory::whereIn('name', ['Đăng ký nghỉ', 'Đăng ký WFH'])->get();
        $idHoliday = $category->where('name', 'Đăng ký nghỉ')->first()->id;
        $idWorkFromHome = $category->where('name', 'Đăng ký WFH')->first()->id;
        $proposesWFM = Propose::where('propose_category_id', $idWorkFromHome)
            ->where('status', 'approved')
            ->where('account_id', $account->id)
            ->whereMonth('date_wfh', $month)
            ->count();
        $account['WFM_this_month'] = $proposesWFM;
        $proposes = Propose::where('propose_category_id', $idHoliday)
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
        if ($account->dayoffAccount != null) {
            $account['day_off'] = $account->dayoffAccount->dayoff_count + $account->dayoffAccount->dayoff_long_time_worker;
            unset($account->dayoffAccount);
        } else {
            unset($account->dayoffAccount);
            $account['day_off'] = 0;
        }
        $account['day_off_used'] = $a;
        $account['avatar'] = $account->avatar;

        return response()->json($account);
    }

    public function storeFiles(Request $request)
    {
        if ($request->hasFile('files')) {
            $file = $request->file('files');
            if (!is_array($file)) {
                $filename = now()->format('Y-m-d') . '_' . $file->getClientOriginalName(); // Ngày + Tên gốc
                $path = $file->storeAs('/public/files', $filename); // Lưu file với tên mới
                $fileUrl = Storage::url($path);
                $fileSizeMB = round($file->getSize() / (1024 * 1024), 2);

                return ['url' => $fileUrl, 'size' => $fileSizeMB . "MB", 'time' => now()->format('Y-m-d H:i:s')];
            } else {
                $dataFiles = $this->uploadFile($file);
                return response()->json($dataFiles);
            }
        } else {
            return response()->json([
                'errors' => 'Không có file được tải lên',
                'message' => 'Không có file được tải lên'
            ], 401);
        }
    }

    private function requestUpdateProfile(array $oldData, array $newData)
    {
        $category = ProposeCategory::where('name', 'Cập nhật thông tin cá nhân')->first();
        $data = [
            'name' => 'Cập nhật thông tin cá nhân',
            'propose_category_id' => $category->id,
            'old_value' => $oldData,
            'new_value' => $newData,
            'account_id' => Auth::user()->id,
        ];
        $propose = Propose::create($data);

        return response()->json($propose);
    }

    public function accountsField()
    {
        $data = [];
        $arrayPersonalInfo = [
            'children' => [
                ['label' => 'Email', 'value' => 'email'],
                ['label' => 'Số điện thoại', 'value' => 'phone'],
                ['label' => 'Họ và tên', 'value' => 'full_name'],
                ['label' => 'Ngày sinh', 'value' => 'birthday'],
                ['label' => 'Giới tính', 'value' => 'gender'],
                ['label' => 'Địa chỉ', 'value' => 'address'],
                ['label' => 'Giấy tờ tùy thân', 'value' => 'personal_documents'],
                ['label' => 'Trạng thái nghỉ việc', 'value' => 'quit_work'],
                ['label' => 'Ảnh đại diện', 'value' => 'avatar'],
                ['label' => 'Ngày nghỉ phép', 'value' => 'day_off'],
                ['label' => 'Tên tài khoản', 'value' => 'username'],
                ['label' => 'Trạng thái', 'value' => 'status'],
                ['label' => 'Chức vụ', 'value' => 'position'],
                ['label' => 'Ngày bắt đầu làm việc', 'value' => 'start_work_date'],
                ['label' => 'Ngày kết thúc làm việc', 'value' => 'end_work_date'],
                ['label' => 'Làm việc tại nhà', 'value' => 'attendance_at_home'],
                ['label' => 'Email cá nhân', 'value' => 'personal_email'],
                ['label' => 'Tên ngân hàng', 'value' => 'name_bank'],
                ['label' => 'Số tài khoản', 'value' => 'bank_number'],
                ['label' => 'Người quản lí', 'value' => 'manager_id'],
                ['label' => 'Số CMND', 'value' => 'identity_card'],
                ['label' => 'Địa chỉ tạm trú', 'value' => 'temporary_address'],
                ['label' => 'Hộ chiếu', 'value' => 'passport'],
                ['label' => 'Mã số thuế', 'value' => 'tax_code'],
                ['label' => 'Tình trạng hôn nhân', 'value' => 'marital_status'],
                ['label' => 'Mức giảm trừ gia cảnh', 'value' => 'tax_reduced'],
                ['label' => 'Chính sách thuế', 'value' => 'tax_policy'],
                ['label' => 'BHXH', 'value' => 'BHXH'],
                ['label' => 'Nơi đăng ký thường trú', 'value' => 'place_of_registration'],
                ['label' => 'Vùng lương', 'value' => 'salary_scale'],
                ['label' => 'Chính sách bảo hiểm', 'value' => 'insurance_policy'],
                ['label' => 'Ngày bắt đầu thử việc', 'value' => 'start_trial_date'],
                ['label' => 'Phân quyền', 'value' => 'role_id'],
            ],
            'name' => 'Thông tin cá nhân',
            'value' => 'personal_info',
        ];
        $arraySalary = [
            'children' => [
                ['label' => 'Lương cơ bản', 'value' => 'basic_salary'],
                ['label' => 'Phụ cấp đi lại', 'value' => 'travel_allowance'],
                ['label' => 'Phụ cấp ăn uống', 'value' => 'eat_allowance'],
                ['label' => 'KPI', 'value' => 'kpi'],
                ['label' => 'Chức vụ', 'value' => 'job_position_id'],
            ],
            'name' => 'Lương',
            'value' => 'salary',
        ];
        $arrayContract = [
            'children' => [
                ['label' => 'Ghi chú', 'value' => 'note'],
                ['label' => 'Loại hợp đồng', 'value' => 'category__contract_id'],
                ['label' => 'Ngày bắt đầu hợp đồng', 'value' => 'contract_start_date'],
                ['label' => 'Ngày kết thúc hợp đồng', 'value' => 'contract_end_date'],
                ['label' => 'Trạng thái của hợp đồng', 'value' => 'contract_status'],
                ['label' => 'Tài liệu hợp đồng', 'value' => 'contract_files'],
                ['label' => 'Người tạo hợp đồng', 'value' => 'creator_by'],
            ],
            'name' => 'Hợp đồng',
            'value' => 'contract',
        ];
        $arrayDepartment = [
            'children' => [
                ['label' => 'Tên phòng ban', 'value' => 'department_name'],
            ],
            'name' => 'Phòng ban',
            'value' => 'department',
        ];
        $arrayEducation = [
            'children' => [
                ['label' => 'Tên trường', 'value' => 'school_name'],
                ['label' => 'Thời gian bắt đầu học', 'value' => 'start_date'],
                ['label' => 'Thời gian kết thúc học', 'value' => 'end_date'],
                ['label' => 'Loại học vấn', 'value' => 'type'],
            ],
            'name' => 'Học vấn',
            'value' => 'education',
        ];
        $data[] = $arrayPersonalInfo;
        $data[] = $arraySalary;
        $data[] = $arrayContract;
        $data[] = $arrayDepartment;
        $data[] = $arrayEducation;

        return $data;
    }

    public function disableAccount(int $id)
    {
        if (Auth::user()->isSeniorAdmin()) {
            $account = Account::query()->findOrFail($id);
            $account->update([
                'quit_work' => true
            ]);
            $account->tokens()->delete();
            AccountWorkflow::where('account_id', $id)->delete();
            // xét các công việc về null
            Task::where('account_id', $id)->update([
                'account_id' => null,
                'started_at' => null,
                'expired' => null,
            ]);

            AccountDepartment::where('account_id', $id)->delete();

            // Xét các tài nguyên về null
            Asset::where('account_id', $id)->update([
                'account_id' => null,
            ]);

            return response()->json([
                'message' => 'Vô hiệu hoá tài khoản thành công'
            ]);
        } else {

            return response()->json([
                'message' => 'Bạn không có quyền vô hiệu hoá tài khoản',
                'error' => 'Bạn không có quyền vô hiệu hoá tài khoản'
            ], 403);
        }
    }

    private function uploadFile($files)
    {
        $dataFiles = [];
        if ($files) {
            foreach ($files as $file) {
                $filename = now()->format('Y-m-d') . '_' . $file->getClientOriginalName(); // Ngày + Tên gốc    
                $path = 'public/files/' . $filename;
                // if (Storage::exists($path)) {
                //     return 0;
                // }
                $path = $file->storeAs('/public/files', $filename); // Lưu file với tên mới
                $fileUrl = Storage::url($path);
                $fileSizeMB = round($file->getSize() / (1024 * 1024), 2);
                $dataFiles[] = [
                    'file_name' => $filename,
                    'file_url' => $fileUrl,
                    'file_size' => $fileSizeMB
                ];
            }
        }

        return $dataFiles;
    }

    public function activeAccount(int $id)
    {
        $account = Account::query()->findOrFail($id);
        $account->update([
            'quit_work' => false
        ]);

        return response()->json([
            'message' => 'Kích hoạt tài khoản thành công'
        ]);
    }

    public function checkPassword(Request $request)
    {
        $account = Auth::user();
        if (Hash::check($request->password, $account->password)) {
            return response()->json([
                'success' => true,
                'message' => 'Mật khẩu chính xác'
            ]);
        }

        return response()->json(    [
            'success' => false,
            'message' => 'Mật khẩu không chính xác'
        ], 401);
    }
}
