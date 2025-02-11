<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\WorkflowStoreRequest;
use App\Http\Requests\WorkflowUpdateRequest;
use App\Models\Account;
use App\Models\AccountWorkflow;
use App\Models\Field;
use App\Models\Stage;
use App\Models\Task;
use App\Models\Workflow;
use App\Models\WorkflowCategoryStage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WorkflowController extends Controller
{
    public function index(Request $request)
    {
        $arrWorkflow = [];
        $workflows = AccountWorkflow::where('account_id', Auth::id())->get();
        $arrWorkflowId = [];
        foreach ($workflows as $workflow) {
            $arrWorkflowId[] = $workflow->workflow_id;
        }
        if (isset($request->type)) {
            if ($request->type == "open") {
                $query = Workflow::where('is_close', '0');
            } elseif ($request->type == "close") {
                $query = Workflow::where('is_close', '1');
            }
        } else {
            $query = Workflow::query();
        }

        if (!Auth::user()->isSeniorAdmin()) {
            $query = $query->whereIn('id', $arrWorkflowId);
        }

        if (isset($request->workflow_category_id)) {
            $query->where('workflow_category_id', $request->workflow_category_id);
        }

        if (isset($request->search)) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%');
            });
        }

        $workflows = $query->get();
        $arrWorkflowId = $workflows->pluck('id');
        $stages = Stage::query()->whereIn('workflow_id', $arrWorkflowId)->get();
        $stagesCompletedAndFailed = Stage::query()
            ->whereIn('workflow_id', $arrWorkflowId)
            ->whereIn('index', [0, 1])
            ->get();
        $tasks = Task::get();
        $members = AccountWorkflow::query()
            ->whereIn('workflow_id', $arrWorkflowId)
            ->get();
        $arrAccountId = $members->pluck('account_id');
        $accounts = Account::query()
            ->whereIn('id', $arrAccountId)
            ->get();

        foreach ($workflows as $workflow) {
            $countTaskFailed = 0;
            $countTaskSuccess = 0;
            // lấy ra stage thất bại
            $allStage = $stages->where('workflow_id', $workflow->id)->pluck('id');
            $stageFailed = $stagesCompletedAndFailed
                ->where('workflow_id', $workflow->id)
                ->where('index', 0)
                ->pluck('id');
            $countTaskFailed = $tasks->whereIn('stage_id', $stageFailed)->count();
            $stageCompleted = $stagesCompletedAndFailed
                ->where('workflow_id', $workflow->id)
                ->where('index', 1)
                ->pluck('id');
            $countTaskSuccess = $tasks->whereIn('stage_id', $stageCompleted)->count();
            $countTask = $tasks->whereIn('stage_id', $allStage)->count();
            $members2 = $members->where('workflow_id', $workflow->id);
            $arr = [
                'totalTask' => $countTask,
                'totalSuccessTask' => $countTaskSuccess ?? 0,
                'totalFailedTask' => $countTaskFailed ?? 0,
            ];

            $arrMember = [];
            foreach ($members2 as $member) {
                $tri = $accounts->where('id', $member->account_id);
                $tri = array_values($tri->toArray());
                $tri = $tri[0];
                if ($tri['role_id'] == 1) {
                    $tri['role'] = 'Admin';
                } else if ($tri['role_id'] == 2) {
                    $tri['role'] = 'Admin lv2';
                } else {
                    $tri['role'] = 'User';
                }
                unset($tri['role_id']);
                $arrMember[] = $tri;
            }
            $a = array_merge($arr, $workflow->toArray());
            $a['members'] = $arrMember;
            $arrWorkflow[] = $a;
        }

        return response()->json($arrWorkflow);
    }

    public function store(WorkflowStoreRequest $request)
    {
        $error = [];
        $name = Workflow::query()->where('workflow_category_id', $request->input('workflow_category_id'))->where('name', $request->name)->first();
        if (isset($name)) {
            $error['name'] = 'Workflow đã tồn tại';
        }
        $accounts = explode(' ', $request->manager);
        foreach ($accounts as $account) {
            $acc = Account::query()->where('username', $account)->first();
            if (!$acc) {
                $error['manager'] = 'Tài khoản không tồn tại';
            }
        }
        if ($error) {
            return response()->json(['errors' => $error], 403);
        }
        $workflow = Workflow::query()->create($request->all());
        foreach ($accounts as $account) {
            $acc = Account::query()->where('username', $account)->first();
            if (isset($acc)) {
                $accWork = AccountWorkflow::query()->where('account_id', $acc->id)->where('workflow_id', $workflow->id)->first();
                if (!$accWork) {
                    AccountWorkflow::query()->create([
                        'account_id' => $acc->id,
                        'workflow_id' => $workflow->id
                    ]);
                }
            }
        }
        //  Truy cập vào bảng workflow_category_stages để lấy ra cac stage được quy định sẵn từ workflow_category
        $stageRules = WorkflowCategoryStage::query()->where('workflow_category_id', $workflow->workflow_category_id)->get();
        $numberIndex = $stageRules->count() + 2;
        foreach ($stageRules as $stageRule) {
            $stage = Stage::query()->create([
                'name' => $stageRule->name,
                'workflow_id' => $workflow->id,
                'index' => $numberIndex
            ]);
            $numberIndex--;
            foreach ($stageRule->reports as $field) {
                Field::query()->create([
                    'name' => $field->name,
                    'workflow_id' => $workflow->id,
                    'stage_id' => $stage->id,
                    'type' => $field->type ?? 'paragraph',
                    'require' => 1,
                    'options' => $field->options ?? null,
                    'model' => 'report-field',
                    'report_rule_id' => $field->id ?? null,
                ]);
            }
        }
        Stage::query()->create([
            'name' => 'Thất bại',
            'workflow_id' => $workflow->id,
            'description' => 'Đánh dấu những công việc không hoàn thành',
            'index' => 0
        ]);
        Stage::query()->create([
            'name' => 'Hoàn thành',
            'workflow_id' => $workflow->id,
            'description' => 'Đánh dấu hoàn thành công việc',
            'index' => 1
        ]);

        return response()->json($workflow);
    }

    public function destroy($id)
    {
        $workflow = Workflow::query()->findOrFail($id);
        $workflow->delete();

        return response()->json(['success' => 'Xoá thành công']);
    }

    public function update(int $id, WorkflowUpdateRequest $request)
    {
        $workflow = Workflow::query()->findOrFail($id);
        $data = $request->all();
        if (isset($data['manager'])) {
            AccountWorkflow::query()->where('workflow_id', $id)->delete();
            $arrManager = explode(' ', $data['manager']);
            foreach ($arrManager as $account) {
                $acc = Account::query()->where('username', $account)->first();
                if (isset($acc)) {
                    AccountWorkflow::query()->create([
                        'account_id' => $acc->id,
                        'workflow_id' => $id,
                    ]);
                }
            }
        }
        $workflow->update($data);

        return response()->json($workflow);
    }

    public function show($id, Request $request)
    {
        $arr = [];
        $arrMember = [];
        $workflow = Workflow::query()->findOrFail($id)->toArray();
        $members = AccountWorkflow::query()->where('workflow_id', $workflow['id'])->get();
        $arrMemberId = $members->pluck('account_id')->toArray();
        $accounts = Account::query()->whereIn('id', $arrMemberId)->get();
        foreach ($accounts as $account) {
            $tri = $account;
            if ($tri->role_id == 1) {
                $tri['role'] = 'Admin';
            } else if ($tri->role_id == 2) {
                $tri['role'] = 'Admin lv2';
            } else {
                $tri['role'] = 'User';
            }
            unset($tri->role_id);
            $tri = $tri->toArray();
            $arrMember[] = $tri;
        }
        $a = array_merge($arr, $workflow);
        $a['members'] = $arrMember;

        return response()->json($a);
    }

    public function search(Request $request)
    {
        if (isset($request->keyword)) {
            $workflows = Workflow::query()->where('name', 'like', '%' . $request->keyword . '%')->get();

            return response()->json($workflows);
        }
    }
}
