<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\WorkflowStoreRequest;
use App\Http\Requests\WorkflowUpdateRequest;
use App\Models\Account;
use App\Models\AccountWorkflow;
use App\Models\Stage;
use App\Models\Task;
use App\Models\Workflow;
use Illuminate\Http\Request;

class WorkflowController extends Controller
{
    public function index(Request $request)
    {
        $arrWorkflow = [];
        if (isset($request->type)) {
            if ($request->type == "open") {
                $query = Workflow::where('is_close', '0');
            } elseif ($request->type == "close") {
                $query = Workflow::where('is_close', '1');
            }
        } else {
            $query = Workflow::query();
        }

        if (isset($request->workflow_category_id)) {
            $query->where('workflow_category_id', $request->workflow_category_id);
        }

        if (isset($request->search)) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%');
            });
        }

        $workflows = $query->get()->toArray();

            foreach ($workflows as $workflow) {
            $countTaskFailed = 0;
            $countTaskSuccess = 0;
            $stageFailed = Stage::query()->where('workflow_id', $workflow['id'])->where('index', 0)->first();
            if (isset($stageFailed)) {
                $countTaskFailed = Task::query()->where('stage_id', $stageFailed->id)->count();
            }
            $stageCompleted = Stage::query()->where('workflow_id', $workflow['id'])->where('index', 1)->first();
            if (isset($stageCompleted)) {
                $countTaskSuccess = Task::query()->where('stage_id', $stageCompleted->id)->count();
            }
            $stages = Stage::query()->where('workflow_id', $workflow['id'])->get()->toArray();
            $totalTask = 0;
            foreach ($stages as $row) {
                $countTask = Task::query()->where('stage_id', $row['id'])->count();
                $totalTask += $countTask;
            }
            $arr = [
                'totalTask' => $totalTask,
                'totalSuccessTask' => $countTaskSuccess ?? 0,
                'totalFailedTask' => $countTaskFailed ?? 0,
            ];
            $arrMember = [];
            $members = AccountWorkflow::query()->where('workflow_id', $workflow['id'])->get();
            foreach ($members as $member) {
                $tri = Account::query()->where('id', $member->account_id)->first()->toArray();
                $arrMember[] = $tri;
            }
            $a = array_merge($arr, $workflow);
            $a['members'] = $arrMember;
            $arrWorkflow[] = $a;
        }
        return response()->json(
            $arrWorkflow,
        );
    }

    public function store(WorkflowStoreRequest $request) {
            $error = [];
            $name =  Workflow::query()->where('workflow_category_id', $request->input('workflow_category_id'))->where('name', $request->name)->first();
            if (isset($name)){
                $error['name'] = 'Workflow đã tồn tại';
            }
            $accounts = explode(' ', $request->manager);
            foreach ($accounts as $account) {
                $acc = Account::query()->where('username', $accounts)->first();
                if (!$acc) {
                   $error['manager'] = 'Tài khoản không tồn tại';
                }
            }
            if ($error) {
                return response()->json(['errors'=>$error], c);
            }

            $workflow = Workflow::query()->create($request->all());
            foreach ($accounts as $account) {
                $acc = Account::query()->where('username', $account)->first();
               if (isset($acc)) {
                   $accWork = AccountWorkflow::query()->where('account_id', $acc->id)->where('workflow_id', $workflow->id)->first();
                   if (!$accWork) {
                       AccountWorkflow::query()->create([
                           'account_id' => $acc->id,
                           'workflow_id'=> $workflow->id
                       ]);
                   }
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

    public function destroy($id) {
            $workflow = Workflow::query()->findOrFail($id);
            $workflow->delete();
            return response()->json(['success'=> 'Xoá thành công']);

    }

    public function showMember($id) {
        $arr = [];
        $members = AccountWorkflow::query()->where('workflow_id', $id)->get();
        foreach ($members as $member) {
            $arr1 = Account::query()->select('email', 'id','username')->where('id', $member->account_id)->first()->toArray();
            $arr[] = $arr1;
        }

        return response()->json($arr);
    }

    public function update(int $id, WorkflowUpdateRequest $request) {
            $workflow = Workflow::query()->findOrFail($id);
            $data = $request->all();
            $workflow->update([
             $data
            ]);

            return response()->json([
                'success' => 'Cập nhập thành công'
            ]);
    }

    public function show($id, Request $request)
    {
        $arr = [];
        $arrMember= [];
        $workflow = Workflow::query()->findOrFail($id)->toArray();
        $members = AccountWorkflow::query()->where('workflow_id', $workflow['id'])->get();
        foreach ($members as $member) {
            $tri = Account::query()->where('id', $member->account_id)->first()->toArray();
            $arrMember[] = $tri;
        }
        $a = array_merge($arr, $workflow);
        $a['members'] = $arrMember;

        return response()->json($a);
    }

    public function search(Request $request)
    {
        if (isset($request->keyword)) {
            $arr = [];
            $workflows = Workflow::query()->where('name', 'like', '%' . $request->keyword . '%')->get();

            return response()->json($workflows);
        }
    }
}
