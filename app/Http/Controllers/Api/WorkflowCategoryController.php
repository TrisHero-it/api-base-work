<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\WorkflowCategoryStoreRequest;
use App\Models\Account;
use App\Models\AccountDepartment;
use App\Models\AccountWorkflow;
use App\Models\AccountWorkflowCategory;
use App\Models\Workflow;
use App\Models\WorkflowCategory;
use App\Models\WorkflowCategoryStage;
use App\Models\WorkflowCategoryStageReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WorkflowCategoryController extends Controller
{
    const IMAGE_PATH = '/images/workflow-categories';

    public function index()
    {
        $categories = WorkflowCategory::query()->get();
        $workflows = AccountWorkflow::where('account_id', Auth::id())->get();
        $arrWorkflowId = $workflows->pluck('workflow_id');
        $accounts = Account::query()->select('id', 'username', 'full_name', 'avatar')->get();
        $accountDepartment = AccountDepartment::query()->get();

        if (Auth::user()->isSeniorAdmin()) {
            $workflows = Workflow::get();
        } else {
            $workflows = Workflow::whereIn('id', $arrWorkflowId)->get();
        }

        foreach ($categories as $category) {
            $category['workflows'] = array_values($workflows->where('workflow_category_id', $category->id)->toArray());
            if (isset($category->department_id)) {
                $category['members'] = array_values($accounts->whereIn('id', $accountDepartment->where('department_id', $category->department_id)->pluck('account_id'))->toArray());
            }
        }

        return response()->json($categories);
    }

    public function update(int $id, Request $request)
    {

        $members2 = AccountWorkflow::query()->where('workflow_id', $request->workflow_id)->get();
        if (!Auth::user()->isAdmin()) {
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

        return $category;
    }

    public function store(WorkflowCategoryStoreRequest $request)
    {
        $workflow = WorkflowCategory::create(
            [
                'name' => $request->name,
            ]
        );

        return response()->json($workflow);
    }

    public function destroy($id)
    {
        $category = WorkflowCategory::query()->findOrFail($id);
        $category->delete();

        return response()->json(['success' => 'Xoá thành công']);
    }

    public function show($id)
    {
        $category = WorkflowCategory::query()->findOrFail($id);
        $a = [];
        $stages = WorkflowCategoryStage::query()->where('workflow_category_id', $id)->get();
        foreach ($stages as $stage) {
            $reports = WorkflowCategoryStageReport::query()->select('name', 'type')->where('report_stage_id', $stage->id)->get();
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
