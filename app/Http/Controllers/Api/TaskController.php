<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\HistoryMoveTask;
use App\Models\Kpi;
use App\Models\Notification;
use App\Models\Stage;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TaskController extends Controller
{
    public function index($id)
    {
        $tasks = Task::query()->where('stage_id', $id)->orderBy('updated_at', 'desc')->get();
        foreach ($tasks as $task) {
            if ($task->expired != null) {
                $deadLine = new \DateTime($task->expired);
                $now = new \DateTime();
                if ($deadLine < $now) {
                    $task->update([
                        'status' => 'Nhiệm vụ quá hạn'
                    ]);
                }
            }
        }

        return response()->json($tasks);
    }

    public function store(Request $request)
    {
        try {
            $account = Account::query()->where('id', $request->account_id)->first() ?? null;
            $stage = Stage::query()->where('workflow_id', $request->workflow_id)->orderByDesc('index')->first();
            $task = Task::query()->create([
                'code' => rand(10000000, 99999999),
                'name' => $request->name,
                'description' => $request->description ?? null,
                'account_id' => $account->id ?? null,
                'stage_id' => $stage->id,
            ]);
            if (isset($account)) {
                if (isset($stage->expired_after_hours)) {
                    $dateTime = Carbon::parse($request->created_at);
                    $task = Task::query()->where('id', $task->id)->first() ?? null;
                    $task->update([
                        'expired' => $dateTime->copy()->addHour($stage->expired_after_hours),
                        'started_at' => $dateTime
                    ]);
                }
            }

            return response()->json([
                'success'=> 'Thêm thành công',
               'id' => $task->code
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'error' => $exception->getMessage()
            ]);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $token = $request->header('Authorization');
            $token = explode(' ', $token)[1];
            $acc = Account::query()->where('remember_token', $token)->first() ?? null;
            $task = Task::query()->where('code', $id)->first();
            if ($request->account_id != null) {
                if ($task->account_id != null && $request->account_id != $task->account_id) {
                    return response()->json([
                        'error' => 'Nhiệm vụ này đã có người nhận rồi'
                    ]);
                }
            }
            if ($task == null) {
                return response()->json([
                    'error' => 'Sai mã code nhiệm vụ'
                ]);
            }
            if (!isset( $request->stage_id) && !isset($request->account_id) && !isset($request->expired)) {
                $b = $request->except('expired');
                $task->update($request->all());
                return response()->json([
                    'success' => 'Chỉnh sửa thành công'
                ]);
            }

//    Chuyển giai đoạn
            $stage = Stage::query()->where('id', $request->stage_id)->first() ?? null;
            if (isset($stage)) {
                $data = $request->except('expired');
                $data['started_at'] = null;
                    $worker = HistoryMoveTask::query()->where('old_stage', $stage->id)->where('task_id', $task->id)->where('worker', '!=', null)->first() ?? null;
                    $data['account_id'] = $worker!=null ? $worker->worker : null;
                    if ($data['account_id'] != null) {
                        $data['started_at'] = $worker->started_at;
                    }
                if ($stage->index == 0) {
                    $data['failed_at'] = $task->stage->name;
                } else {
                    $data['failed_at'] = null;
                    $data['reason'] = null;
                }

                if ($task->stage_id != $stage->id) {
                    if ($stage->index > $task->stage->index) {
                        $a =  Kpi::query()->where('task_id', $task->id)->where('stage_id', $stage->id)->first();
                        if ($a != null) {
                            $a->delete();
                        }
                    }

                    HistoryMoveTask::query()->create([
                        'account_id' => $acc->id,
                        'task_id' => $task->id,
                        'old_stage' => $task->stage_id,
                        'new_stage' => $stage->id,
                        'started_at' => $task->started_at ?? null,
                        'worker' => $task->account_id,
                        'expired_at'=> $task->expired ?? null,
                    ]);
                    if ($task->expired > now() || $task->expired == null) {
                        $j = 1;
                    }else {
                        $j =0;
                    }
                   if ($task->account_id != null && $task->stage->index > $stage->index) {
                       Kpi::query()->create([
                           'account_id' => $task->account_id,
                           'stage_id' => $task->stage_id,
                           'task_id' => $task->id,
                           'status' => $j
                       ]);
                   }
                }

                $data['expired'] = null;

                if ($stage->name == 'Hoàn thành') {
                    if ($task->account_id != null) {
                        $task->update($data);
                    } else {
                        return response()->json([
                            'error' => 'Chưa có người nhận nhiệm vụ'
                        ]);
                    }
                } else if ($stage->name == 'Thất bại') {
                    if ($task->account_id != null) {
                        $task->update($data);
                        return response()->json([
                            'success' => 'Chỉnh sửa thành công'
                        ]);
                    } else {
                        return response()->json([
                            'error' => 'Chưa có người nhận nhiệm vụ'
                        ]);
                    }
                } else {
//  giao việc
                    if (isset($request->account_id)) {
                        Notification::query()->create([
                            'title' => 'Nhiệm vụ mới cho bạn',
                            'message' => 'Nhiệm vụ ' .$task->name. ' được ' . $acc->username . ' giao cho bạn',
                            'link' => 'https://work.1997.pro.vn/workflows/'.$task->stage->workflow->id,
                            'account_id'=> $request->account_id,
                        ]);
                        $data['started_at'] = now();
                    }
                    if (isset($stage->expired_after_hours)) {
                        $data['expired'] = now()->addHours($stage->expired_after_hours);
                    }
                    $task->update(
                        $data
                    );
                    return response()->json([
                        'success' => 'Chỉnh sửa thành công'
                    ]);
                }
            }else if (isset($request->account_id)){
                $data = $request->all();
                $data['started_at'] = now();
                Notification::query()->create([
                    'title' => 'Nhiệm vụ mới cho bạn',
                    'message' => 'Nhiệm vụ ' .$task->name. ' được ' . $acc->username . ' giao cho bạn',
                    'link' => 'https://work.1997.pro.vn/workflows/'.$task->stage->workflow->id.'?seen=1&task='.$task->id,
                    'account_id'=> $request->account_id,
                ]);
                if ($task->stage->expired_after_hours != null) {
                    $data['expired'] = now()->addHours($task->stage->expired_after_hours);
                }
                $task->update(
                    $data
                );

                return response()->json([
                    'success' => 'Sửa thành công'
                ]);
            }
        } catch (\Exception $exception) {
            return response()->json([
                'error' => 'Đã xảy ra lỗi : ' . $exception->getMessage()
            ]);
        }
    }

    public function show(int $id)
    {
        $task = Task::query()->where('code', $id)->first();

        return response()->json($task);
    }

    public function destroy($id)
    {
        try {
            $task = Task::query()->where('code', $id)->first();
            $task->delete();
            return response()->json([
                'success' => 'Xoá thành công'
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'error' => 'Đã xảy ra lỗi : ' . $exception->getMessage()
            ]);
        }
    }

    public function uploadImage(Request $request)
    {
        try {
            $image = $request->file('image');
            if (!isset($image)) {
                return response()->json(['error' => 'file ảnh không tồn tại'], 500);
            }
            $imageUrl = Storage::put('/public/images', $image);
            $imageUrl = Storage::url($imageUrl);
            return response()->json(['urlImage' => 'https://work.1997.pro.vn/api/' . $imageUrl]);
        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 500);
        }
    }
}
