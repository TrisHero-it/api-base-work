<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\WorkflowCategoryStoreRequest;
use App\Http\Requests\WorkflowStoreRequest;
use App\Models\Account;
use App\Models\AccountWorkflowCategory;
use App\Models\Workflow;
use App\Models\WorkflowCategory;
use App\Models\WorkflowCategoryStage;
use App\Models\WorkflowCategoryStageReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class WorkflowCategoryController extends Controller
{
    const IMAGE_PATH =  '/images/workflow-categories';

    public function index() {
        $categories = WorkflowCategory::get()->toArray();
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

    public function update(int $id, Request $request) {
        $category = WorkflowCategory::query()->findOrFail($id);
        $category->update($request->all());
        if (isset($request->members)) {
            $members = explode(' ', $request->members);
            AccountWorkflowCategory::query()->where('workflow_category_id', $id)->delete();
            foreach ($members as $member) {
                $accountId = Account::query()->where('username', $member)->value('id');
                AccountWorkflowCategory::query()->create([
                    'workflow_category_id' => $id,
                    'account_id' => $accountId
                ]);
            }
        }

//        if (isset($request->rules)) {
//            foreach ($request->rules as $rule) {
//             $workflowCategory = WorkflowCategoryStage::query()->where('workflow_category_id', $id)->delete();
//                Workflow::query()->create([
//                    'workflow_category_id' => $id,
//                    'name' => $rule->stage_name
//                ]);
//            foreach($rule->reports as $report) {
//                WorkflowCategoryStageReport::query()->create([
//                    'report_stage_id' => $workflowCategory->id,
//                    'name' => $report->name,
//                    'type' => $report->type,
//                ]);
//            }
//            }
//        }

        return $category;
    }

    public function store(WorkflowCategoryStoreRequest $request)
    {
            $err = [];
            $arrs = explode('@', $request->members);
            foreach ($arrs as $arr) {
                if (trim($arr)!= '') {
                    $acc = Account::query()->where('username', '@'.trim($arr))->first();
                    if (!$acc) {
                        $err['members'] = 'Tài khoản không tồn tại';
                    }
                }
            }
            if ($err) {
                return response()->json(['errors' => $err], 422);
            }
           $workflow = WorkflowCategory::create(
                [
                    'name' => $request->name,
                ]
            );
//  Thêm thanh vien cho workflow
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

//  Thêm stage mặc định cho các workflow con ở trong
        if (isset($request->rules)) {
            $arrStage = $request->rules;
            foreach ($arrStage as $stage) {
                $a = WorkflowCategoryStage::query()->create([
                    'workflow_category_id' => $workflow->id,
                    'name' => $stage['stage_name'],
                ]);
                $reports =$stage['reports'];
                if (!empty($reports)) {
                    foreach ($reports as $report) {
                        WorkflowCategoryStageReport::query()->create([
                            'report_stage_id' => $a->id,
                            'name' => $report['name'],
                            'type' => $report['type'],
                        ]);
                    }
                }
            }
        }

            return response()->json($workflow);
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

    public function show($id) {
        $category = WorkflowCategory::query()->findOrFail($id);
        $a = [];
        $stages = WorkflowCategoryStage::query()->where('workflow_category_id', $id)->get();
        foreach ($stages as $stage) {
            $reports = WorkflowCategoryStageReport::query()->select('name','type')->where('report_stage_id', $stage->id)->get();
            $a[] = [
                'stage_name' => $stage->name,
                'report' => $reports,
            ];
        }
        $category['rules'] = $a;
        $b = [];
        $members = AccountWorkflowCategory::query()->where('workflow_category_id', $category->id)->get();
        foreach ($members as $member) {
            $b[] = $member->account;
        }
        $category['members'] = $b;
        return response()->json($category);
    }

}
