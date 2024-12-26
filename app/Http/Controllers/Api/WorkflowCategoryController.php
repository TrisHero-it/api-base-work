<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\WorkflowCategoryStoreRequest;
use App\Http\Requests\WorkflowStoreRequest;
use App\Models\Account;
use App\Models\AccountWorkflow;
use App\Models\AccountWorkflowCategory;
use App\Models\Department;
use App\Models\Workflow;
use App\Models\WorkflowCategory;
use App\Models\WorkflowCategoryStage;
use App\Models\WorkflowCategoryStageReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
            if (isset($member->account_id)) {
                $arrMembers[]  = Account::query()->select('id','email','created_at','updated_at','username','full_name')->find($member->account_id);
            }else {
                $b = Department::query()->where('id', $member->department_id)->first();
                $arrMembers[]  = [
                    'username' => "$member->department_id",
                    'full_name' => $b->name,
                    'type' => 'department',
                ];
            }
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

        $members2 = AccountWorkflow::query()->where('workflow_id', $request->workflow_id)->get();
        if (!Auth::user()->isSeniorAdmin()) {
            $flag = 0;
            foreach ($members2 as $member) {
                if ($member->account_id == Auth::id()) {
                    $flag = 1;
                }
            }
            if ($flag == 0) {
                return response()->json([
                    'errors' => 'Bạn không phải là thành viên của workflow này'
                ], 403);
            }
        }

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
           $arrs = explode('@', $request->members);

           $workflow = WorkflowCategory::create(
                [
                    'name' => $request->name,
                ]
            );
//  Thêm thanh vien cho workflow
            foreach ($arrs as $arr) {
                if (trim($arr) != ''){
                    $acc = Account::query()->where('username', '@'.trim($arr))->first();
                    if (isset($acc)) {
                        $work = AccountWorkflowCategory::query()->where('account_id', $acc->id)->where('workflow_category_id', $workflow->id)->first();
                        if (!$work) {
                            AccountWorkflowCategory::query()->create([
                                'account_id' => $acc->id,
                                'workflow_category_id' => $workflow->id,
                            ]);
                        }
                    }else {
                        AccountWorkflowCategory::query()->create([
                            'department_id' => $arr,
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
                if (isset($stage['reports']))
                {
                    $reports = $stage['reports'];
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
