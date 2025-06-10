<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProposeStoreRequest;
use App\Models\Account;
use App\Models\AccountDepartment;
use App\Models\Attendance;
use App\Models\DateHoliday;
use App\Models\DayoffAccount;
use App\Models\Department;
use App\Models\Education;
use App\Models\FamilyMember;
use App\Models\JobPosition;
use App\Models\Notification;
use App\Models\Propose;
use App\Models\ProposeCategory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProposeController extends Controller
{
    const FIELDS = [
        ['label' => 'Email', 'value' => 'email'],
        ['label' => 'Thâm niên', 'value' => 'seniority'],
        ['label' => 'Số điện thoại', 'value' => 'phone'],
        ['label' => 'Họ và tên', 'value' => 'full_name'],
        ['label' => 'Ngày sinh', 'value' => 'birthday'],
        ['label' => 'Giới tính', 'value' => 'gender'],
        ['label' => 'Địa chỉ', 'value' => 'address'],
        ['label' => 'Hợp đồng lao động', 'value' => 'contract_files'],
        ['label' => 'Giấy tờ tùy thân', 'value' => 'personal_documents'],
        ['label' => 'Ngày nghỉ phép', 'value' => 'day_off'],
        ['label' => 'Tên tài khoản', 'value' => 'username'],
        ['label' => 'Trạng thái', 'value' => 'status'],
        ['label' => 'Chức vụ', 'value' => 'position'],
        ['label' => 'Ngày bắt đầu', 'value' => 'start_work_date'],
        ['label' => 'Ngày kết thúc', 'value' => 'end_work_date'],
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
        ['label' => 'Lương thực nhận', 'value' => 'net_salary'],
        ['label' => 'Lương cơ bản', 'value' => 'basic_salary'],
        ['label' => 'Phụ cấp đi lại', 'value' => 'travel_allowance'],
        ['label' => 'Phụ cấp ăn uống', 'value' => 'eat_allowance'],
        ['label' => 'KPI', 'value' => 'kpi'],
        ['label' => 'Chức vụ', 'value' => 'position'],
        ['label' => 'Loại hợp đồng', 'value' => 'contract_type'],
        ['label' => 'Ghi chú', 'value' => 'note'],
        ['label' => 'Loại hợp đồng', 'value' => 'category__contract_id'],
        ['label' => 'Ngày bắt đầu hợp đồng', 'value' => 'contract_start_date'],
        ['label' => 'Ngày kết thúc hợp đồng', 'value' => 'contract_end_date'],
        ['label' => 'Trạng thái của hợp đồng', 'value' => 'status'],
        ['label' => 'Tên trường', 'value' => 'school_name'],
        ['label' => 'Thời gian bắt đầu học', 'value' => 'start_date'],
        ['label' => 'Thời gian kết thúc học', 'value' => 'end_date'],
        ['label' => 'Loại học vấn', 'value' => 'type'],
        ['label' => 'Tên phòng ban', 'value' => 'department_name'],
        ['label' => 'Quan hệ', 'value' => 'relationship'],
        ['label' => 'Tên của người thân', 'value' => 'name'],
        ['label' => 'Số điện thoại', 'value' => 'phone_number'],
        ['label' => 'Phụ thuộc', 'value' => 'is_dependent'],
    ];
    public function index(Request $request)
    {
        if (isset($request->include)) {
            $proposes = Propose::where('status', 'pending')
                ->get()
                ->count();

            return response()->json($proposes);
        }
        $perPage = $request->per_page ?? 10;
        $proposes = Propose::query()->with(['account', 'propose_category', 'date_holidays', 'approved_by'])
            ->orderByRaw("CASE WHEN status = 'Đang chờ duyệt' THEN 1 ELSE 2 END")
            ->orderBy('created_at', 'desc');

        if (isset($request->status)) {
            $proposes = $proposes->where('status', $request->status);
        }

        if ($request->filled('code')) {
            $proposes = $proposes->where('id', $request->code);
        }

        if ($request->filled('start_date') || $request->filled('end_date')) {
            $startDate = $request->filled('start_date') ? Carbon::parse($request->start_date) : Carbon::parse('2004-01-01');
            $endDate = $request->filled('end_date') ? Carbon::parse($request->end_date) : Carbon::parse('3000-01-01');
            $proposes = $proposes->whereBetween('created_at', [$startDate, $endDate]);
        }

        if (isset($request->propose_category_id)) {
            $proposes = $proposes->where('propose_category_id', $request->propose_category_id);
        }

        if (!Auth::user()->isSeniorAdmin()) {
            $proposes = $proposes->where('account_id', Auth::id());
        }

        if ($request->filled('account_id')) {
            $proposes = $proposes->where('account_id', $request->account_id);
        }

        if (isset($request->date)) {
            $date = explode("-", $request->date);
            $year = $date[0];
            $month = $date[1];
            $proposes = $proposes->whereMonth('created_at', $month)->whereYear('created_at', $year);
        }
        $count = [];
        $count['pending'] = Propose::where('status', 'pending')->count();
        $count['approved'] = Propose::where('status', 'approved')->count();
        $count['canceled'] = Propose::where('status', 'canceled')->count();

        $proposes = $proposes->paginate($perPage);
        foreach ($proposes as $propose) {
            $propose['date'] = $propose->date_holidays;
            $propose['account'] = $propose->account;
            $propose['avatar'] = $propose->account->avatar;
            $propose['category_name'] = $propose->propose_category_id == null ? 'Tuỳ chỉnh' : $propose->propose_category->name;
        }

        return response()->json([
            'current_page' => $proposes->currentPage(),
            'data' => $proposes->items(),
            'per_page' => $proposes->perPage(),
            'last_page' => $proposes->lastPage(),
            'from' => $proposes->firstItem(),
            'to' => $proposes->lastItem(),
            'total_pages' => $proposes->lastPage(),
            'total' => $proposes->total(),
            'count' => $count
        ]);
    }

    public function show(int $id)
    {
        $propose = Propose::with(['account', 'date_holidays', 'propose_category', 'approved_by'])
            ->findOrFail($id);
        if ($propose->propose_category->name == 'Đăng ký nghỉ') {
            $numberHoliDay = 0;
            foreach ($propose->date_holidays as $date2) {
                $startDate = Carbon::parse($date2->start_date);
                $endDate = Carbon::parse($date2->end_date);
                for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
                    // Nếu như không phải ngày đầu hay là ngày cuối, thì sẽ +1 ngày công luôn
                    if ($date->format('Y-m-d') != $startDate->format('Y-m-d') && $date->format('Y-m-d') != $endDate->format('Y-m-d')) {
                        $numberHoliDay++;
                    } else {
                        $innerStart1 = Carbon::parse($date->format("Y-m-d") . " 08:30:00");
                        $innerEnd1 = Carbon::parse($date->format("Y-m-d") . " 12:00:00");
                        $innerStart2 = Carbon::parse($date->format("Y-m-d") . " 13:30:00");
                        $innerEnd2 = Carbon::parse($date->format("Y-m-d") . " 17:30:00");
                        if ($innerStart1->greaterThanOrEqualTo($startDate) && $innerEnd1->lessThanOrEqualTo($endDate)) {
                            $numberHoliDay = $numberHoliDay + number_format(3.5 / 7.5, 3);
                        } else {
                            $validStart = max($innerStart1, $startDate);
                            $validEnd = min($innerEnd1, $endDate);
                            if ($validStart->lessThan($validEnd)) {
                                $validHours = $validStart->floatDiffInHours($validEnd, true);
                                $numberHoliDay += number_format($validHours / 7.5, 3);
                            }
                        }
                        if ($innerStart2->greaterThanOrEqualTo($startDate) && $innerEnd2->lessThanOrEqualTo($endDate)) {
                            $numberHoliDay = $numberHoliDay + number_format(4 / 7.5, 3);
                        } else {
                            $validStart = max($innerStart2, $startDate);
                            $validEnd = min($innerEnd2, $endDate);
                            if ($validStart->lessThan($validEnd)) {
                                $validHours = $validStart->floatDiffInHours($validEnd, true);
                                $numberHoliDay += number_format($validHours / 7.5, 3);
                            }
                        }
                    }
                }
            }
            $propose['number_holiday'] = $numberHoliDay;
        }
        $arrayMerge =  [
            'education',
            'family_member',
            'history_works',
            'job_position',
            'salary',
            'dayoff_account',
        ];
        if ($propose->old_value != null) {
            $data = $propose->old_value;
            foreach ($arrayMerge as $key) {
                if (isset($data[$key])) {
                    $data = array_merge($data, $data[$key]);
                    unset($data[$key]);
                }
            }

            if ($data != null) {
                foreach (self::FIELDS as $field) {
                    $key = $field['value'];
                    if (array_key_exists($key,  $data)) {
                        $result[$field['label']] =  $data[$key];
                    }
                }
                $propose['old_value'] = $result;
            }

            $data = $propose->new_value;

            foreach ($arrayMerge as $key) {
                if (isset($data[$key])) {
                    $data = array_merge($data, $data[$key]);
                    unset($data[$key]);
                }
            }

            if ($data != null) {
                foreach (self::FIELDS as $field) {
                    $key = $field['value'];
                    if (array_key_exists($key,  $data)) {
                        $result[$field['label']] =  $data[$key];
                    }
                }
                $propose['new_value'] = $result;
            }
        }

        return response()->json($propose);
    }

    public function store(ProposeStoreRequest $request)
    {
        $data = $request->except('holiday');
        $data['account_id'] = Auth::id();
        $accounts = Account::where('role_id', 3)->get();
        $proposeCategory = ProposeCategory::where('id', $request->propose_category_id)->first();
        if ($request->filled('propose_category')) {
            $proposeCategory = ProposeCategory::where('name', $request->propose_category)->first();
            $data['propose_category_id'] = $proposeCategory->id;
        }
        if ($request->name == 'Sửa giờ vào ra') {
            $date = explode(' ', $request->start_time)[0];
            $attendance = Attendance::whereDate('checkin', $date)
                ->where('account_id', Auth::id())
                ->first();
            if ($attendance != null) {
                $data['old_check_in'] = $attendance->checkin;
                $data['old_check_out'] = $attendance->checkout;
            } else {
                $data['old_check_in'] = null;
                $data['old_check_out'] = null;
            }

            $arrNotifications = [];
            foreach ($accounts as $account) {
                $arrNotifications[] = [
                    'account_id' => $account->id,
                    'title' => 'Yêu cầu sửa giờ vào ra',
                    'message' => "<strong>" . Auth::user()->full_name . "</strong> đã gửi yêu cầu sửa giờ vào ra",
                    "link" => env('APP_URL') . "/request",
                    "manager_id" => Auth::id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            Notification::insert($arrNotifications);
        }
        $numberHoliDay = 0;
        // if ($request->filled('holiday')) {
        //     $request->validate([
        //         'holiday.start_date' => ['required', 'date_format:Y-m-d'],
        //     ], [
        //         'holiday.start_date.required' => 'Vui lòng nhập ngày bắt đầu.',
        //         'holiday.start_date.date_format' => 'Ngày bắt đầu phải có định dạng YYYY-MM-DD.',
        //     ]);
        // }
        if ($proposeCategory->name == "Đăng ký nghỉ") {
            foreach ($request->holiday as $date2) {
                $startDate = Carbon::parse($date2['start_date']);
                $endDate = Carbon::parse($date2['end_date']);
                for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
                    // Nếu như không phải ngày đầu hay là ngày cuối, thì sẽ +1 ngày công luôn
                    if ($date->format('Y-m-d') != $startDate->format('Y-m-d') && $date->format('Y-m-d') != $endDate->format('Y-m-d')) {
                        $numberHoliDay++;
                    } else {
                        $innerStart1 = Carbon::parse($date->format("Y-m-d") . " 08:30:00");
                        $innerEnd1 = Carbon::parse($date->format("Y-m-d") . " 12:00:00");
                        $innerStart2 = Carbon::parse($date->format("Y-m-d") . " 13:30:00");
                        $innerEnd2 = Carbon::parse($date->format("Y-m-d") . " 17:30:00");
                        if ($innerStart1->greaterThanOrEqualTo($startDate) && $innerEnd1->lessThanOrEqualTo($endDate)) {
                            $numberHoliDay = $numberHoliDay + number_format(3.5 / 7.5, 3);
                        } else {
                            $validStart = max($innerStart1, $startDate);
                            $validEnd = min($innerEnd1, $endDate);
                            if ($validStart->lessThan($validEnd)) {
                                $validHours = $validStart->floatDiffInHours($validEnd, true);
                                $numberHoliDay += number_format($validHours / 7.5, 3);
                            }
                        }
                        if ($innerStart2->greaterThanOrEqualTo($startDate) && $innerEnd2->lessThanOrEqualTo($endDate)) {
                            $numberHoliDay = $numberHoliDay + number_format(4 / 7.5, 3);
                        } else {
                            $validStart = max($innerStart2, $startDate);
                            $validEnd = min($innerEnd2, $endDate);
                            if ($validStart->lessThan($validEnd)) {
                                $validHours = $validStart->floatDiffInHours($validEnd, true);
                                $numberHoliDay += number_format($validHours / 7.5, 3);
                            }
                        }
                    }
                }
            }
            if ($request->name == "Nghỉ có hưởng lương") {
                $dayOffAccount = DayoffAccount::where('account_id', Auth::id())->first();
                if ($dayOffAccount->dayoff_count - $numberHoliDay < 0) {
                    return response()->json([
                        'message' => 'Số ngày nghỉ vượt quá số ngày nghỉ của bạn',
                        'errors' => 'Số ngày nghỉ vượt quá số ngày nghỉ của bạn'
                    ], status: 401);
                }
            }

            $arrNotifications = [];
            foreach ($accounts as $account) {
                $arrNotifications[] = [
                    'account_id' => $account->id,
                    'title' => 'Yêu cầu xin nghỉ',
                    'message' => "<strong>" . Auth::user()->full_name . "</strong> đã gửi yêu cầu xin nghỉ",
                    "link" => env('APP_URL') . "/request",
                    "manager_id" => Auth::id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            Notification::insert($arrNotifications);
        }
        if ($proposeCategory->name == "Đăng ký làm ở nhà") {
            $count = Propose::where('account_id', Auth::id())
                ->where('status', 'approved')
                ->whereMonth('date_wfh', now()->month)
                ->count();
            if ($count >= 5) {
                return response()->json([
                    'message' => 'Bạn đã đăng ký làm ở nhà quá 5 lần trong tháng',
                    'errors' => 'Bạn đã đăng ký làm ở nhà quá 5 lần trong tháng'
                ], status: 401);
            }

            $arrNotifications = [];
            foreach ($accounts as $account) {
                $arrNotifications[] = [
                    'account_id' => $account->id,
                    'title' => 'Yêu cầu xin làm ở nhà',
                    'message' => "<strong>" . Auth::user()->full_name . "</strong> đã gửi yêu cầu xin làm ở nhà",
                    "link" => env('APP_URL') . "/request",
                    "manager_id" => Auth::id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            Notification::insert($arrNotifications);
        }

        $arr = [];
        $propose = Propose::query()->create($data);
        if (isset($request->holiday)) {
            foreach ($request->holiday as $date) {
                if ($proposeCategory->name == "Đăng ký nghỉ") {
                    $a = [
                        'propose_id' => $propose->id,
                        'number_of_days' => $numberHoliDay
                    ];
                } else {
                    $a = [
                        'propose_id' => $propose->id,
                    ];
                }

                $arr[] = array_merge($a, $date);
            }
            DateHoliday::query()->insert($arr);
        }

        return response()->json($propose);
    }

    public function update(int $id, Request $request)
    {
        if (isset($request->status)) {
            if (!Auth::user()->isSeniorAdmin()) {
                return response()->json([
                    'message' => 'Bạn không có quyền thao tác',
                    'errors' => 'Bạn không có quyền thao tác'
                ], status: 401);
            }
        }
        $propose = Propose::query()->with('propose_category')->findOrFail($id);
        $dayOffAccount = DayoffAccount::where('account_id', $propose->account_id)->first();
        $data = $request->all();
        if (isset($request->status)) {
            $data['approved_by'] = Auth::id();
        }

        if ($request->status == 'approved' && $propose->propose_category->name == 'Nghỉ có hưởng lương') {
            $numberHoliDay = 0;
            foreach ($propose->date_holidays as $date2) {
                $numberHoliDay += $date2->number_of_days;
            }
            if ($dayOffAccount->dayoff_count - $numberHoliDay < 0) {
                return response()->json([
                    'message' => 'Số ngày nghỉ vượt quá số ngày nghỉ của bạn',
                    'errors' => 'Số ngày nghỉ vượt quá số ngày nghỉ của bạn'
                ], status: 401);
            }
            $dayOffAccount->update([
                'dayoff_count' => $dayOffAccount->dayoff_count - $numberHoliDay
            ]);
        }
        $propose->update($data);
        if ($request->status == 'approved' && $propose->propose_category->name == 'Sửa giờ vào ra') {
            $date = explode(' ', $propose->start_time)[0];
            $attendance = Attendance::whereDate('checkin', $date)->where('account_id', $propose->account_id)
                ->first();
            if ($attendance != null) {
                $attendance->update([
                    'checkin' => $propose->start_time,
                    'checkout' => $propose->end_time,
                    'edited_at' => now(),
                ]);
            } else {
                Attendance::create([
                    'checkin' => $propose->start_time,
                    'checkout' => $propose->end_time,
                    'account_id' => $propose->account_id,
                    'edited_at' => now(),
                ]);
            }
        }
        if ($request->status == 'approved' && $propose->name == 'Cập nhật thông tin cá nhân') {
            $account = Account::where('id', $propose->account_id)->first();
            $data = $propose->new_value;
            if (isset($data['education'])) {
                if (isset($data['education']['id'])) {
                    Education::where('id', $data['education']['id'])->update([
                        'school_name' => $data['education']['school_name'],
                        'start_date' => $data['education']['start_date'],
                        'end_date' => $data['education']['end_date'],
                        'major' => $data['education']['major'],
                        'degree' => $data['education']['degree'],
                    ]);
                } else {
                    Education::create([
                        'school_name' => $data['education']['school_name'],
                        'start_date' => $data['education']['start_date'],
                        'end_date' => $data['education']['end_date'],
                        'major' => $data['education']['major'],
                        'degree' => $data['education']['degree'],
                        'account_id' => $propose->account_id,
                    ]);
                }
            }

            if (isset($data['family_member'])) {
                if (isset($data['family_member']['id'])) {
                    FamilyMember::where('id', $data['family_member']['id'])->update([
                        $data['family_member']
                    ]);
                } else {
                    $data['family_member']['account_id'] = $propose->account_id;
                    FamilyMember::create([
                        $data['family_member']
                    ]);
                }
            }

            if (isset($data['department_name'])) {
                $department = Department::where('name', $data['department_name'])->first();
                AccountDepartment::where('account_id', $propose->account_id)->update([
                    'department_id' => $department->id
                ]);
            }

            if (isset($data['position'])) {
                $jobPosition = JobPosition::where('name', $data['position'])
                    ->where('account_id', $propose->account_id)
                    ->where('status', 'active')
                    ->first();
                if ($jobPosition == null) {
                    JobPosition::where('account_id', $propose->account_id)->update([
                        'status' => 'inactive'
                    ]);
                    JobPosition::create([
                        'account_id' => $propose->account_id,
                        'name' => $data['position'],
                        'status' => 'active',
                    ]);
                }
            }

            $account->update($data);
        }

        $name = $propose->propose_category->name;
        $status = $propose->status == 'approved' ? 'được chấp nhận' : 'bị từ chối';
        Notification::create([
            'account_id' => $propose->account_id,
            'title' => "$name của bạn đã " . $status,
            'message' => "<strong>$name</strong> của bạn đã " . $status,
            'manager_id' => auth()->id()
        ]);

        return response()->json($propose);
    }

    public function destroy(int $id)
    {
        $propose = Propose::query()->findOrFail($id);
        $propose->delete();

        return response()->json($propose);
    }
}
