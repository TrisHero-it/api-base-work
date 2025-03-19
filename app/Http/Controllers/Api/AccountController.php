<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AccountStoreRequest;
use App\Http\Requests\AccountUpdateRequest;
use App\Models\Account;
use App\Models\AccountDepartment;
use App\Models\AccountWorkflow;
use App\Models\Attendance;
use App\Models\DateHoliday;
use App\Models\Department;
use App\Models\Education;
use App\Models\FamilyMember;
use App\Models\JobPosition;
use App\Models\Propose;
use App\Models\ProposeCategory;
use App\Models\Salary;
use App\Models\Task;
use App\Models\View;
use App\Models\WorkHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class   AccountController extends Controller
{
    public function register(AccountStoreRequest $request)
    {
        $email = $request->safe()->email;
        $username = $request->safe()->username ?? explode('@', $email)[0];
        $account = Account::create([
            'email' => $email,
            'password' => Hash::make($request->safe()->password),
            'username' => $username,
            'full_name' => $request->full_name,
            'day_off' => 0
        ]);

        return response()->json($account);
    }

    public function update(int $id, AccountUpdateRequest $request)
    {
        $account = Account::query()->findOrFail($id);
        $models = [
            'education' => Education::class,
            'work_history' => WorkHistory::class,
            'family_member' => FamilyMember::class,
            'job_position' => JobPosition::class,
            'salary' => Salary::class,
        ];

        if (Auth::user()->isSeniorAdmin()) {
            $data = $request->except('password', 'avatar', 'position', 'department_id');

            foreach ($models as $key => $model) {
                $arr = [];
                if ($request->filled($key)) {
                    if (isset($request->$key['id']) && $key != 'job_position') {
                        $model::where('id', $request->$key['id'])->update($request->$key);
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
            $account->update($data);
            if ($request->filled('position')) {
                $jobPosition = JobPosition::where('status', 'active')
                    ->where('account_id', $id)
                    ->where('name', '!=', $request->position)
                    ->first();
                if (isset($jobPosition)) {
                    $jobPosition->update([
                        'status' => 'inactive'
                    ]);
                    $salary = Salary::where('job_position_id', $jobPosition->id)->first();
                    $jobPosition2 = JobPosition::create([
                        'account_id' => $id,
                        'name' => $request->position,
                        'status' => 'active',
                    ]);

                    Salary::create([
                        'job_position_id' => $jobPosition2->id,
                        'gross_salary' => $salary->gross_salary,
                        'travel_allowance' => $salary->travel_allowance,
                        'eat_allowance' => $salary->eat_allowance,
                        'net_salary' => $salary->net_salary,
                        'kpi' => $salary->kpi,
                        'basic_salary' => $salary->basic_salary,
                    ]);
                }
            }

            return response()->json($account);
        }

        //  Nếu không phải là admin thì cập nhập sẽ thành yêu cầu sửa thông tin
        $oldData = Account::select($request->keys())
            ->where('id', $id)
            ->first();

        foreach ($models as $key => $model) {
            if ($request->filled($key)) {
                $oldData[$key] = $model::where('account_id', $id)
                    ->get();
            }
        }

        $this->requestUpdateProfile($oldData->toArray(), $request->all());

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
                    $dataSelect = ['full_name'];
                    $dataWith = [];
                    if (isset($view->field_name['personal_info'])) {
                        $dataSelect = array_merge($dataSelect, $view->field_name['personal_info']);
                    }
                    if (isset($view->field_name['salary'])) {
                        $dataWith = array_merge($dataWith, ['jobPosition.salary']);
                    }
                    if (isset($view->field_name['contract'])) {
                        $dataWith = array_merge($dataWith, ['contract']);
                    }
                    $accounts = Account::select($dataSelect)
                        ->with('jobPosition');
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

        foreach ($accounts as $account) {
            if (!empty($account->department->toArray())) {
                $account->department_name = $account->department[0]->name;
            }
            unset($account->department);
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
            $account = Account::with(['educations', 'workHistories', 'familyMembers', 'jobPosition' => function ($query) {
                $query->with('salary') // Vẫn lấy đầy đủ thông tin từ salary
                    ->addSelect('job_positions.*')
                    ->leftJoin('salaries', 'job_positions.id', '=', 'salaries.job_position_id')
                    ->addSelect(DB::raw('(salaries.gross_salary + salaries.travel_allowance + salaries.eat_allowance) as total_salary'));
            }, 'contracts.category', 'department'])
                ->where('id', Auth::id())
                ->first();
            $salary = Salary::where('job_position_id', $account->jobPosition->where('status', 'active')->first()->id)->first();
            $account->salary = $salary;
            $account->department_name = $account->department[0]->name;
            unset($account->department);
            $account->position = $account->jobPosition->where('status', 'active')->first()->name;
        } else if ($request->include == 'my-job') {
            $account = Account::with(['jobPosition' => function ($query) {
                $query->with('salary') // Vẫn lấy đầy đủ thông tin từ salary
                    ->addSelect('job_positions.*')
                    ->leftJoin('salaries', 'job_positions.id', '=', 'salaries.job_position_id')
                    ->addSelect(DB::raw('(salaries.gross_salary + salaries.travel_allowance + salaries.eat_allowance) as total_salary'));
            }])->where('id', Auth::id())->first();
            $salary = Salary::where('job_position_id', $account->jobPosition->where('status', 'active')->first()->id)->first();
            $account->salary = $salary;
        } else {
            $account = Account::select('id', 'username', 'full_name', 'avatar', 'role_id', 'email', 'phone', 'day_off')
                ->where('id', Auth::id())
                ->first();
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
        if ($request->hasFile('files')) {
            $file = $request->file('files');
            $filename = now()->format('Y-m-d') . '_' . $file->getClientOriginalName(); // Ngày + Tên gốc
            $path = 'public/files/' . $filename;
            if (Storage::exists($path)) {
                return response()->json([
                    'error' => 'File đã tồn tại!'
                ], 409);
            }
            $path = $file->storeAs('/public/files', $filename); // Lưu file với tên mới
            $fileUrl = Storage::url($path);
            $fileSizeMB = round($file->getSize() / (1024 * 1024), 2);
        }

        return ['url' => $fileUrl, 'size' => $fileSizeMB . "MB", 'time' => now()->format('Y-m-d H:i:s')];
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
                ['label' => 'Hợp đồng lao động', 'value' => 'contract_file'],
                ['label' => 'Giấy tờ tùy thân', 'value' => 'personal_documents'],
                ['label' => 'Trạng thái nghỉ việc', 'value' => 'quit_work'],
                ['label' => 'Ảnh đại diện', 'value' => 'avatar'],
                ['label' => 'Tài liệu', 'value' => 'files'],
                ['label' => 'Ngày nghỉ phép', 'value' => 'day_off'],
                ['label' => 'Tên tài khoản', 'value' => 'username'],
                ['label' => 'Mật khẩu', 'value' => 'password'],
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
                ['label' => 'Lương gross', 'value' => 'gross_salary'],
                ['label' => 'Lương thực nhận', 'value' => 'net_salary'],
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
                ['label' => 'Loại hợp đồng', 'value' => 'contract_type'],
                ['label' => 'Ghi chú', 'value' => 'note'],
                ['label' => 'Loại hợp đồng', 'value' => 'category__contract_id'],
                ['label' => 'Ngày bắt đầu hợp đồng', 'value' => 'contract_start_date'],
                ['label' => 'Ngày kết thúc hợp đồng', 'value' => 'contract_end_date'],
                ['label' => 'Trạng thái của hợp đồng', 'value' => 'status'],
            ],
            'name' => 'Hợp đồng',
            'value' => 'contract',
        ];
        $arrayDepartment = [
            'children' => [
                ['label' => 'Tên phòng ban', 'value' => 'name'],
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

    public function disableAccount(int $id, Request $request)
    {
        $account = Account::query()->findOrFail($id);
        $account->update([
            'quit_work' => true
        ]);
        $account->tokens()->delete();
        AccountWorkflow::where('account_id', $id)->delete();
        Task::where('account_id', $id)->update([
            'account_id' => null,
            'started_at' => null,
            'expired' => null,
        ]);

        return response()->json([
            'message' => 'Vô hiệu hoá tài khoản thành công'
        ]);
    }
}
