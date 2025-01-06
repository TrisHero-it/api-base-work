<?php

namespace App\Http\Controllers\Api;

use App\Events\HistoryMoveTaskEvent;
use App\Events\KpiEvent;
use App\Events\NotificationEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\TaskStoreRequest;
use App\Models\Account;
use App\Models\AccountWorkflow;
use App\Models\AccountWorkflowCategory;
use App\Models\HistoryMoveTask;
use App\Models\Kpi;
use App\Models\Notification;
use App\Models\Stage;
use App\Models\StickerTask;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $tasks = Task::query()
            ->with('tags')
            ->latest('updated_at')
            ->get();

        return response()->json($tasks);
    }

    public function store(TaskStoreRequest $request)
    {
            $account = Account::query()->find($request->account_id);
            $stage = Stage::query()
                ->where('workflow_id', $request->workflow_id)
                ->orderByDesc('index')
                ->first();
            $members = AccountWorkflow::query()
                ->where('workflow_id', $request->workflow_id)
                ->get();
            if (!Auth::user()->isSeniorAdmin()) {
                $flag = 0;
                foreach ($members as $member) {
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
            if ($stage->isSuccessStage()) {
                return response()->json([
                    'errors' => 'Chưa có giai đoạn'
                ], 500);
            }

            $task = Task::query()->create([
                'name' => $request->name,
                'description' => $request->description ?? null,
                'account_id' => $account->id ?? null,
                'stage_id' => $stage->id,
            ]);
            if (isset($account)) {
                if (isset($stage->expired_after_hours)) {
                    $dateTime = Carbon::parse($request->created_at);
                    $task = Task::query()
                        ->where('id', $task->id)
                        ->first() ?? null;
                    $task->update([
                        'expired' => $dateTime->addHour($stage->expired_after_hours),
                        'started_at' => $dateTime
                    ]);
                }
            }

            return response()->json($task);
    }


    public function update($id, TaskStoreRequest $request)
    {
        $account = Auth::user();
        $task = Task::query()->find($id);
        if (isset($request->tag_id)) {
            $arrTag = $request->tag_id;
            StickerTask::query()->where('task_id', $task->id)->delete();
            $tag = [];
            foreach ($arrTag as $tagId) {
                $tag[] = StickerTask::query()->create([
                    'task_id' => $task->id,
                    'sticker_id' => $tagId
                ]);
            }
        }

    
        if ($account->id != $task->account_id && !isset($request->account_id) && !$account->isAdmin()) {
            return response()->json([
                'message' => 'Nhiệm vụ này không phải của bạn',
                'errors' => [
                    'task' => 'Nhiệm vụ này không phải của bạn'
                ]
            ], 403);
        }
    if (isset($request->stage_id)) {
    //  Lấy ra stage mà mình muốn chuyển đến
    $stage = Stage::query()->where('id', $request->stage_id)->first();

    if ($stage->isFailStage() && !$account->isSeniorAdmin()) {
        return response()->json([
            'message' => 'Bạn không thể cho nhiệm vụ thất bại',
            'errors' => [
                'task' => 'Bạn không thể cho nhiệm vụ thất bại'
            ]
        ], 403);
    };
    }
        // Cập nhập thông tin nhiệm vụ
        $data = $request->except('expired');

        if(isset($request->expired_at)) {
            if(isset($task->expired)){
                $expired = new \DateTime($task->expired);
            }else {
                $expired = new \DateTime(now());
            }

            $data['expired'] = $expired->modify('+' . $request->expired_at . ' hours');
        }


        if (isset($request->link_youtube)){
        // Nếu có link youtube thì lấy ra mã code của link đó
            preg_match('/v=([a-zA-Z0-9_-]+)/', $request->link_youtube, $matches);
        // Phân biệt youtube shorts
            if (strpos($request->link_youtube, 'shorts') !== false) {
                $aa = explode('/', $request->link_youtube);
                $data['code_youtube'] = end($aa);
            } else {
                $data['code_youtube'] = $matches[1];
            }
        }

        //  Nếu có tồn tại account_id thì là giao việc cho người khác thì thêm thông báo
        //  Nếu account_id == null thì là gỡ người làm nhiệm vụ
        if (isset($request->account_id) && $request->account_id == null)
        {
            if ($task->account_id != $account->id) {
                if (!$account->isAdmin()) {
                    return response()->json([
                        'message' => 'Bạn không có quyền gỡ nhiệm vụ này, load lại đi men',
                        'errors' => [
                            'task' => 'Bạn không có quyền gỡ nhiệm vụ này, load lại đi men'
                        ],
                    ], 403);
                }
            }
        }
            if ($task->account_id != $request->account_id && $request->account_id != null) {
        //  Nếu không phải admin thì không cho phép sửa nhiệm vụ đã có người nhận rồi
                if ($task->account_id != null) {
                    if (!$account->isAdmin()) {
                        return response()->json([
                            'message' => 'Nhiệm vụ này đã có người nhận, load lại đi men',
                            'errors' => [
                                'task' => 'Nhiệm vụ này đã có người nhận, load lại đi men'
                            ],
                        ], 403);
                    }
                }
                $data['started_at'] = now();

                if($task->stage->expired_after_hours != null) {
                    $dateTime = new \DateTime($data['started_at']);
                    $dateTime->modify('+' . $task->stage->expired_after_hours . ' hours');
                    $data['expired'] = $dateTime->format('Y-m-d H:i:s');
                }else {
                    $data['expired'] = null;
                }
                    event(new NotificationEvent([
                        'full_name' => $account->full_name,
                        'task_name' => $task->name,
                        'workflow_id' => $task->stage->workflow_id,
                        'account_id' => $request->account_id
                    ]));
            }

        //  Nếu có tồn tại stage_id thì là chuyển giai đoạn
        if ($task->stage_id != $request->stage_id && $request->stage_id != null) {
            if ($task->stage->isSuccessStage()) {
                $data['link_youtube'] = null;
                $data['code_youtube'] = null;
                $data['view_count'] = 0;
                $data['like_count'] = 0;
                $data['comment_count'] = 0;
                $data['date_posted'] = null;
                $data['completed_at'] = null;
                $data['status'] = null;
            }

        //  Chuyển đến giai đọan hoàn thành phải có người làm mới chuyển được
            if ($stage->isSuccessStage()) {
                if ($task->account_id == null) {
                    return response()->json([
                        'errors' => 'Nhiệm vụ chưa được giao'
                    ], 500);
                }else {
                    $data['completed_at'] = now();
                    $data['status'] = 'completed';
                }
            }

        //  Lấy thông tin từ bảng kéo thả nhiệm vụ để hiển thị lại người nhận nhiệm vụ ở giai đoạn cũ
            $worker = HistoryMoveTask::query()
                ->where('task_id', $task->id)
                ->where('old_stage', $request->stage_id)
                ->orderBy('id', 'desc')
                ->first() ?? null;
            if ($worker !== null) {
                $data['expired'] = $worker->expired_at;
                $data['account_id'] = $worker->worker;
                $data['started_at'] = $worker->started_at;
            }else {
                $data['expired'] = null;
                $data['account_id'] = null;
                $data['started_at'] = null;
            }

        //  Nếu giai đoạn có hạn thì nhiệm vụ sẽ ăn theo hạn của giai đoạn
            if (isset($stage->expired_after_hours) && $data['expired'] === null && $data['account_id'] !== null) {
                $dateTime = now();
                $dateTime->addHours($task->stage->expired_after_hours);
                $data['expired'] = $dateTime->format('Y-m-d H:i:s');
            }
        //  Thêm lịch sử kéo thả nhiệm vụ
            event(new HistoryMoveTaskEvent([
                'account_id' => $account->id,
                'task_id' => $task->id,
                'old_stage' => $task->stage_id,
                'new_stage' => $request->stage_id,
                'started_at' => $task->started_at ?? null,
                'worker' => $task->account_id ?? null,
                'expired_at'=> $task->expired ?? null,
            ]));

        //  Nếu như nhiệm vụ đã thành công mà bị chuyển sang thất bại, thì sẽ xóa tát cả kpi của những người làm nhiệm vụ đó
    if ($stage->isFailStage()) {
        $a = Kpi::query()->where('task_id', $task->id)->get();
        $date = new \DateTime($task->created_at);
        $now = new \DateTime();
        foreach ($a as $item) {
            if ($date->format('Y-m') == $now->format('Y-m')) {
                $item->delete();
            }else {
                Kpi::query()->create([
                    'status' => 1,
                    'task_id' => $item->task_id,
                    'stage_id' => $item->stage_id,
                    'account_id' => $item->account_id,
                ]);
            }
        }
    }
        //  Nếu như là chuyển tiếp giao đoạn thì thêm cho 1 kpi
            if ($task->isNextStage($stage->index) && $task->account_id != null && !$stage->isFailStage()) {
                $a = HistoryMoveTask::query()->where('task_id', $task->id)
                    ->where('old_stage', $task->stage_id)
                    ->orderBy('id','desc')
                    ->first();
                $date1 = new \DateTime($a->started_at);
                $date2 = new \DateTime($a->created_at);
                $interval = $date1->diff($date2);
                $totalSeconds = ($interval->days * 24 * 3600) + ($interval->h * 3600) + ($interval->i * 60) + $interval->s;
                $totalMinutes = $totalSeconds / 60;
                $totalHours = $totalMinutes / 60;
                event(new KpiEvent([
                    'account_id' => $task->account_id,
                    'task_id' => $task->id,
                    'stage_id' => $task->stage_id,
                    'status' => 0,
                    'total_time' => $totalHours .'h',
                ]));
            } else {
               $kpi = Kpi::query()->where('task_id', $task->id)->where('stage_id', $request->stage_id)->first() ?? null;
               if ($kpi !== null) {
                   $kpi->delete();
               }
            };
        }
        $task->update($data);
        if (isset($tag)) {
            $task['tag'] = $tag;
        }

        return $task;
    }

    public function show(int $id)
    {
        $task = Task::query()->findOrFail($id);
        $task['sticker'] = StickerTask::query()->where('task_id', $task->id)->get();

        return response()->json($task);
    }

    public function destroy($id)
    {
        try {
            $task = Task::query()->findOrFail($id);
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

    public function loadYoutube(Request $request)
    {
        $a = [];
        // Tuần này
        $endOfThisWeek = Carbon::now()->endOfWeek()->toDateString();
        // Tuần trước
        $startOfLastWeek = Carbon::now()->subWeek()->startOfWeek()->toDateString();
            $tasks = Task::query()->where('code_youtube', '!=', null)->whereBetween('completed_at', [$startOfLastWeek, $endOfThisWeek]);
            $stages = Stage::query()->where('workflow_id', $request->workflow_id)->get();
            foreach ($stages as $stage) {
                $a[] = $stage->id;
            }
            $tasks = $tasks->whereIn('stage_id', $a)->get();

            foreach ($tasks as $task) {
                $videoId = $task->code_youtube; // Thay VIDEO_ID bằng ID của video YouTube
                $apiKey = 'AIzaSyCHenqeRKYnGVIJoyETsCgXba4sQAuHGtA'; // Thay YOUR_API_KEY bằng API key của bạn

                $url = "https://www.googleapis.com/youtube/v3/videos?id={$videoId}&key={$apiKey}&part=snippet,contentDetails,statistics";
                $response = file_get_contents($url);
                $data = json_decode($response, true);
                if($data['items'] == null) {
                    continue;
                }
                $dateTime = new \DateTime($data['items'][0]['snippet']['publishedAt']);
                $dateTime->setTimezone(new \DateTimeZone('Asia/Ho_Chi_Minh'));
                $valueData = [
                    'view_count' => $data['items'][0]['statistics']['viewCount'],
                    'like_count' => $data['items'][0]['statistics']['likeCount'],
                    'comment_count' => $data['items'][0]['statistics']['commentCount'],
                    'date_posted' => $dateTime,
                ];
                $task->update($valueData);
            }

            return response()->json([
                'success' => 'Cập nhập thành công'
            ]);

    }

}
