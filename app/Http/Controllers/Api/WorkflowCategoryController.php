<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AccountWorkflowCategory;
use App\Models\Workflow;
use App\Models\WorkflowCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class WorkflowCategoryController extends Controller
{
    const IMAGE_PATH =  '/images/workflow-categories';

    public function index() {
        $categories = WorkflowCategory::get()->toArray();
        $arr =[] ;
        $abc = [];
        foreach ($categories as $category) {
            $arrMembers = [];
            $members = AccountWorkflowCategory::query()->where('workflow_category_id', $category['id'])->get();
            foreach ($members as $member) {
            $arrMembers[]  = Account::query()->select('id','email','created_at','updated_at','username')->find($member->account_id);
            }
            $a = [];
            $a['members'] = $arrMembers;
            $arrWorkflows = [];
            $workflows = Workflow::query()->where('workflow_category_id', $category['id'])->get();
            $arrWorkflows['workflows'] = $workflows;
            $arr = array_merge($category, $a, $arrWorkflows);
            $abc[] = $arr;
        }
        return response()->json($abc);
    }

    public function store(Request $request)
    {
        try {
            $err = [];
            $arrs = explode('@', $request->members);

            $categories = WorkflowCategory::all();
            foreach ($categories as $category) {
                $te = WorkflowCategory::query()->where('name', $request->name)->first();
                if ($te) {
                    $err['name'] = 'Danh mục đã tồn tại';
                }
            }
            foreach ($arrs as $arr) {
                if (trim($arr)!= '') {
                    $acc = Account::query()->where('username', '@'.trim($arr))->first();
                    if (!$acc) {
                        $err['members'] = 'Tài khoản không tồn tại';
                    }
                }
            }
            if ($err) {
                return response()->json(['error' => $err], 422);
            }
           $workflow = WorkflowCategory::create(
                [
                    'name' => $request->name,
                ]
            );
            foreach ($arrs as $arr) {
                if (trim($arr) != ''){
                    $acc = Account::query()->where('username', '@'.trim($arr))->first();
                    $work = AccountWorkflowCategory::query()->where('account_id', $acc->id)->where('workflow_category_id', $workflow->id)->first();
                    if (!$work) {
                        AccountWorkflowCategory::query()->create([
                            'account_id' => $acc->id,
                            'workflow_category_id' => $workflow->id,
                        ]);
                    }
                }
            }
            return response()->json(['success' => 'Thêm thành công',
                'id' => $workflow->id]);
        }catch (\Exception $exception){
            return response()->json(['error' => 'Đã xảy ra lỗi'], 500);
        }
    }

    public function destroy($id) {
        try {
            $category = WorkflowCategory::query()->findOrFail($id);
            $category->delete();
            return response()->json(['success' => 'Xoá thành công']);
        }catch (\Exception $exception){
            return response()->json(['error' => 'Đã xảy ra lỗi'], 500);
        }
    }

}
