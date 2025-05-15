<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            ['label' => 'CCCD', 'value' => 'identity_card'],
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
            ['label' => 'Loại hợp đồng', 'value' => 'contract_type'],
            ['label' => 'Ghi chú', 'value' => 'note'],
            ['label' => 'Loại hợp đồng', 'value' => 'category__contract_id'],
            ['label' => 'Ngày bắt đầu hợp đồng', 'value' => 'contract_start_date'],
            ['label' => 'Ngày kết thúc hợp đồng', 'value' => 'contract_end_date'],
            ['label' => 'Trạng thái của hợp đồng', 'value' => 'contract_status'],
            ['label' => 'Tên trường', 'value' => 'school_name'],
            ['label' => 'Thời gian bắt đầu học', 'value' => 'start_date'],
            ['label' => 'Thời gian kết thúc học', 'value' => 'end_date'],
            ['label' => 'Loại học vấn', 'value' => 'type'],
            ['label' => 'Tên phòng ban', 'value' => 'department_name'],
            ['label' => 'Ảnh đại diện', 'value' => 'avatar'],
            ['label' => 'Trạng thái nghỉ việc', 'value' => 'quit_work'],
        ],
        'name' => 'Thông tin cá nhân',
        'value' => 'personal_info',
    ];
    public function index()
    {
        $views = View::orderBy('index', 'asc')->get();
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
            if (isset($view->field_name['education'])) {
                $array = array_merge($array, $view->field_name['education']);
            }
            if (isset($view->field_name['department'])) {
                $array = array_merge($array, $view->field_name['department']);
            }

            $configMap = collect($array)->keyBy('value');
            $result = collect(self::ARRAY_PERSONAL_INFO['children'])
                ->filter(function ($field) use ($configMap) {
                    return $configMap->has($field['value']);
                })
                ->map(function ($field) use ($configMap) {
                    $field['index'] = $configMap[$field['value']]['index'];
                    return $field;
                })
                ->sortBy('index')
                ->values(); // Reset lại key

            $view->field_name = $result;

            $overView[] = $view;
        }

        return response()->json($overView);
    }

    public function store(Request $request)
    {
        $lastestView = View::orderBy('index', 'desc')->first();
        $view = View::create([
            'name' => $request->name,
            'field_name' => $request->field_name,
            'index' => isset($lastestView) ? $lastestView->index + 1 : 0,
        ]);
        return response()->json($view);
    }

    public function show($id)
    {
        $view = View::findOrFail($id);
        $array = [];
        $arrField = ['personal_info', 'salary', 'contract', 'education', 'department'];
        $a = [];
        foreach ($arrField as $field) {
            if (isset($view->field_name[$field])) {
                foreach ($view->field_name[$field] as $newField) {
                    $data = [];
                    $data['type'] = $field;
                    $data = array_merge($data, $newField);
                    $a[] = $data;
                }
                $array = array_merge($array, $a);
            }
        }
        $configMap = collect($array)->keyBy('value');
        $result = collect(self::ARRAY_PERSONAL_INFO['children'])
            ->filter(function ($field) use ($configMap) {
                return $configMap->has($field['value']);
            })
            ->map(function ($field) use ($configMap) {
                $field['index'] = $configMap[$field['value']]['index'];
                $field['type'] = $configMap[$field['value']]['type'];
                return $field;
            })
            ->sortBy('index')
            ->values(); // Reset lại key

        $view->field_name = $result;

        return response()->json($view);
    }

    public function update(Request $request, $id)
    {
        $view = View::find($id);
        if (isset($request->index)) {
            if ($view->isHigherView($request->index)) {
                View::where('index', '>=', $request->index)
                    ->where('index', '<=', $view->index)
                    ->update(['index' => DB::raw('`index` + 1')]);

                $view->update($request->all());
            } else if ($view->isLowerView($request->index)) {
                View::where('index', '>=', $view->index)
                    ->where('index', '<=', $request->index)
                    ->update(['index' => DB::raw('`index` - 1')]);

                $view->update($request->all());
            }
        }

        if (isset($request->field_name)) {
            $view->field_name = $request->field_name;
            $view->save();
        }

        return response()->json($view);
    }

    public function destroy($id)
    {
        $view = View::find($id);
        $view->delete();

        return response()->json([
            'message' => 'Xóa thành công',
        ]);
    }

    public function updateIndexView(Request $request)
    {
        $cases = '';
        $ids = [];
        foreach ($request->all() as $key => $value) {
            $id = (int) $value['id'];
            $index = (int) $key;
            $cases .= "WHEN {$id} THEN {$index} ";
            $ids[] = $id;
        }

        if (!empty($ids)) {
            $idsStr = implode(',', $ids);
            DB::update("UPDATE views SET `index` = CASE id {$cases} END WHERE id IN ({$idsStr})");
        }
        $views = View::orderBy('index', 'asc')->get();

        return response()->json($views);
    }
}
