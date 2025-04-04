<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\View;
use Illuminate\Http\Request;

class ViewController extends Controller
{
    const ARRAY_PERSONAL_INFO = [
        'children' => [
            ['label' => 'Email', 'value' => 'email'],
            ['label' => 'Thâm niên', 'value' => 'seniority'],
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
            ['label' => 'Lương gross', 'value' => 'gross_salary'],
            ['label' => 'Lương thực nhận', 'value' => 'net_salary'],
            ['label' => 'Lương cơ bản', 'value' => 'basic_salary'],
            ['label' => 'Phụ cấp đi lại', 'value' => 'travel_allowance'],
            ['label' => 'Phụ cấp ăn uống', 'value' => 'eat_allowance'],
            ['label' => 'KPI', 'value' => 'kpi'],
            ['label' => 'Chức vụ', 'value' => 'job_position_id'],
            ['label' => 'Loại hợp đồng', 'value' => 'contract_type'],
            ['label' => 'Ghi chú', 'value' => 'note'],
            ['label' => 'Loại hợp đồng', 'value' => 'category__contract_id'],
            ['label' => 'Ngày bắt đầu hợp đồng', 'value' => 'contract_start_date'],
            ['label' => 'Ngày kết thúc hợp đồng', 'value' => 'contract_end_date'],
            ['label' => 'Trạng thái của hợp đồng', 'value' => 'status'],
            ['label' => 'Tên phòng ban', 'value' => 'name'],
            ['label' => 'Tên trường', 'value' => 'school_name'],
            ['label' => 'Thời gian bắt đầu học', 'value' => 'start_date'],
            ['label' => 'Thời gian kết thúc học', 'value' => 'end_date'],
            ['label' => 'Loại học vấn', 'value' => 'type'],
        ],
        'name' => 'Thông tin cá nhân',
        'value' => 'personal_info',
    ];
    public function index()
    {

        $views = View::all();
        $overView = [
            [
                "id" => 0,
                "name" => "Tổng quan",
                "field_name" => [
                    ['label' => 'Nhân sự', 'value' => 'full_name'],
                    ['label' => 'Tài khoản', 'value' => 'username'],
                    ['label' => 'Email', 'value' => 'email'],
                    ['label' => 'Trạng thái', 'value' => 'status'],
                    ['label' => 'Chức vụ', 'value' => 'position'],
                    ['label' => 'Ngày bắt đầu', 'value' => 'start_work_date'],
                    ['label' => 'Ngày kết thúc', 'value' => 'end_work_date'],
                    ['label' => 'Phòng ban', 'value' => 'department_name'],
                    ['label' => 'Giới tính', 'value' => 'gender'],
                    ['label' => 'Hợp đồng lao động', 'value' => 'url_contract'],
                    ['label' => 'Số điện thoại', 'value' => 'phone'],
                    ['label' => 'Ngày sinh', 'value' => 'birthday'],
                    ['label' => 'Thâm niên', 'value' => 'seniority'],
                    ['label' => 'Ngày nghỉ phép', 'value' => 'day_off'],
                    ['label' => 'Giấy tờ tùy thân', 'value' => 'personal_documents'],
                    ['label' => 'Trạng thái nghỉ việc', 'value' => 'quit_work'],
                ]
            ]
        ];
        foreach ($views as $view) {
            $array = [];
            if (isset($view->field_name['personal_info'])) {
                $array = array_merge($array, $view->field_name['personal_info']);
            }
            if (isset($view->field_name['salary'])) {
                $array = array_merge($array, $view->field_name['salary']);
            }
            if (isset($view->field_name['contract'])) {
                $array = array_merge($array, $view->field_name['contract']);
            }
            $filteredArray = array_filter(self::ARRAY_PERSONAL_INFO['children'], function ($item) use ($array) {
                return in_array($item['value'], $array);
            });
            $view->field_name = array_values($filteredArray);
            $overView[] = $view;
        }

        return response()->json($overView);
    }

    public function store(Request $request)
    {
        $view = View::create([
            'name' => $request->name,
            'field_name' => $request->field_name,
        ]);
        return response()->json($view);
    }
}
