<?php

namespace App\Http\Controllers\Api;

use App\Events\HistoryMoveTaskEvent;
use App\Events\KpiEvent;
use App\Events\NotificationEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\TaskStoreRequest;
use App\Models\Account;
use App\Models\AccountWorkflow;
use App\Models\Field;
use App\Models\FieldTask;
use App\Models\HistoryMoveTask;
use App\Models\Kpi;
use App\Models\Stage;
use App\Models\StatusTask;
use App\Models\StickerTask;
use App\Models\Task;
use App\Models\Workflow;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
            ->where('workflow_id', operator: $request->workflow_id)
            ->orderByDesc('index')
            ->first();

        if (!$this->checkMemberWorkflow($request->workflow_id)) {
            return response()->json([
                'message' => 'Bạn không phải là thành viên của workflow này'
            ], 401);
        }


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
                ], 401);
            }
        }
        if ($stage->isSuccessStage()) {
            return response()->json([
                'errors' => 'Chưa có giai đoạn'
            ], 401);
        }
        $data = [
            'name' => $request->name,
            'description' => $request->description ?? null,
            'account_id' => $account->id ?? null,
            'stage_id' => $stage->id,
            'creator_by' => Auth::id(),
            'expired' => $request->expired != null ? Carbon::parse($request->expired) : null,
        ];
        if (isset($request->account_id)) {
            $data['delivery_date'] = now();
        }
        $task = Task::query()->create($data);
        if (isset($account)) {
            event(new NotificationEvent([
                'full_name' => $account->full_name,
                'task_name' => $task->name,
                'workflow_id' => $task->stage->workflow_id,
                'account_id' => $request->account_id,
                'manager_id' => Auth::id(),
            ]));
            if (isset($stage->expired_after_hours)) {
                $dateTime = Carbon::parse($request->created_at);
                $task = Task::query()
                    ->where('id', $task->id)
                    ->first() ?? null;
                $task->update([
                    'expired' => $dateTime->addHours($stage->expired_after_hours),
                    'started_at' => $dateTime
                ]);
            }
        }

        if ($request->filled('fields')) {
            $this->storeFieldTask($task, $request);
        }

        return response()->json($task);
    }

    public function update($id, TaskStoreRequest $request)
    {
        $account = Auth::user();
        $task = Task::query()->find($id);

        if ($request->filled('first_stage')) {
            $this->firstStage($task);
            return response()->json([
                'message' => 'Nhiệm vụ đã được đưa về giai đoạn đầu'
            ]);
        }

        if ($request->filled('previous_stage')) {
            $this->previousStage($task);
            return response()->json([
                'message' => 'Nhiệm vụ đã được đưa về giai đoạn trước'
            ]);
        }

        if ($request->filled('failed_stage')) {
            $this->failedStage($task);
            return response()->json([
                'message' => 'Nhiệm vụ đã được đánh thất bại'
            ]);
        }

        if ($request->filled('next_stage')) {
            $this->nextStage($task);
            return response()->json([
                'message' => 'Nhiệm vụ đã được đưa đến giai đoạn tiếp theo'
            ]);
        }

        if ($request->filled('fields')) {
            $this->updateFieldTask($task, $request);
        }

        if (isset($request->tag_id)) {
            $arrTag = $request->tag_id;
            StickerTask::query()
                ->where('task_id', $task->id)
                ->delete();

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
            ], 401);
        }
        if ($request->filled('stage_id')) {
            $nextStage  = Stage::where('id', $request->stage_id)->first();
            if ($task->isNextStage($nextStage->index) && $task->started_at == null && $task->account_id != null) {
                return response()->json([
                    'message' => 'Phải bấm bắt đầu trước khi chuyển giai đoạn',
                    'errors' => [
                        'task' => 'Phải bấm bắt đầu trước khi chuyển giai đoạn'
                    ]
                ], 401);
            }
        }
        if (isset($request->stage_id)) {
            //  Lấy ra stage mà mình muốn chuyển đến
            $stage = Stage::query()->where('id', $request->stage_id)->first();

            if ($stage->isFailStage() && !$account->isSeniorAdmin()) {
                return response()->json([
                    'message' => 'Bạn không có quyền đánh thất bại nhiệm vụ',
                    'errors' => [
                        'task' => 'Bạn không có quyền đánh thất bại nhiệm vụ'
                    ]
                ], 401);
            };
        }
        // Cập nhập thông tin nhiệm vụ
        $data = $request->all();
        if (isset($request->link_youtube)) {
            // Nếu có link youtube thì lấy ra mã code của link đó
            preg_match('/v=([a-zA-Z0-9_-]+)/', $request->link_youtube, $matches);
            // Phân biệt youtube shorts
            if (strpos($request->link_youtube, 'shorts') !== false) {
                $aa = explode('/', $request->link_youtube);
                $data['code_youtube'] = end($aa);
            } else if (strpos($request->link_youtube, 'youtu.be') !== false) {
                $aa = explode('/', $request->link_youtube);
                $data['code_youtube'] = end($aa);
                if (strpos($data['code_youtube'], '?') !== false) {
                    $data['code_youtube'] = explode('?', $data['code_youtube'])[0];
                }
            } else {
                $data['code_youtube'] = $matches[1];
            }
        }
        //  Nếu có tồn tại account_id thì là giao việc cho người khác thì thêm thông báo
        //  Nếu account_id == null thì là gỡ người làm nhiệm vụ
        if (isset($request->account_id) && $request->account_id == null) {
            if ($task->account_id != $account->id) {
                if (!$account->isAdmin()) {
                    return response()->json([
                        'message' => 'Bạn không có quyền gỡ nhiệm vụ này, load lại đi men',
                        'errors' => [
                            'task' => 'Bạn không có quyền gỡ nhiệm vụ này, load lại đi men'
                        ],
                    ], 401);
                }
            }
        }
        if ($request->account_id != null) {
            //  Nếu không phải admin thì không cho phép sửa nhiệm vụ đã có người nhận rồi
            if ($task->account_id != null) {
                if (!$account->isAdmin() && $task->account_id != Auth::id()) {
                    return response()->json([
                        'message' => 'Nhiệm vụ này đã có người nhận, load lại đi men',
                        'errors' => [
                            'task' => 'Nhiệm vụ này đã có người nhận, load lại đi men'
                        ],
                    ], 401);
                }

                if ($request->account_id == Auth::id() && $task->account_id == Auth::id() && $request->name == null) {
                    $data['started_at'] = now();
                    if ($task->stage->expired_after_hours != null && $task->expired == null) {
                        $dateTime = new \DateTime($data['started_at']);
                        $dateTime->modify('+' . $task->stage->expired_after_hours . ' hours');
                        $data['expired'] = $dateTime->format('Y-m-d H:i:s');
                    }
                    // $this->updateStatusTask($id, $request->account_id, $task->stage_id, 'progress');
                }
            }
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
                // Nếu như là key workflow thì sẽ chuyển đến giai đoạn tiếp theo
                if (isset($request->workflow_id)) {
                    $data['stage_id'] = $this->forwardTask($task, $request->workflow_id);
                }
                if ($stage->workflow->is_key_workflow && !isset($request->workflow_id)) {
                    return response()->json([
                        'message' => 'Vui lòng điền workflow bạn muốn hướng tới',
                    ], 401);
                }

                if ($stage->workflow->require_link_youtube && !isset($request->link_youtube)) {
                    return response()->json([
                        'message' => 'Vui lòng điền link video của bạn',
                    ], 401);
                }
                if ($task->started_at == null) {
                    return response()->json([
                        'message' => 'Nhiệm vụ chưa được giao',
                        'errors' => [
                            'task' => 'Nhiệm vụ chưa được giao'
                        ]
                    ], 401);
                } else {
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
            } else {
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
                'expired_at' => $task->expired ?? null,
            ]));
            //  Nếu như nhiệm vụ đã thành công mà bị chuyển sang thất bại, thì sẽ xóa tát cả kpi của những người làm nhiệm vụ đó
            if ($stage->isFailStage()) {
                $a = Kpi::query()->where('task_id', $task->id)->get();
                $date = new \DateTime($task->created_at);
                $now = new \DateTime();
                foreach ($a as $item) {
                    if ($date->format('Y-m') == $now->format('Y-m')) {
                        $item->delete();
                    } else {
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
                    ->orderBy('id', 'desc')
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
                    'total_time' => $totalHours . 'h',
                ]));
            } else {
                $latestHistory = HistoryMoveTask::query()->where('task_id', $task->id)->where('old_stage', $request->stage_id)->latest('id')->first();
                if ($latestHistory) {
                    $latestHistory->update([
                        'status' => 'skipped'
                    ]);
                }

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

    public function assignWork(int $id, Request $request)
    {
        if (Auth::user()->isAdmin() || $request->account_id == Auth::id()) {
            $task = Task::with('stage')->findOrFail($id);
            $data = [];
            $this->updateStatusTask($id, $request->account_id, $task->stage_id, 'progress');

            if (isset($request->account_id)) {
                $workflow = Workflow::where('id', $task->stage->workflow_id)->first();
                $isMemberWorkflow = AccountWorkflow::where('workflow_id', $workflow->id)
                    ->where('account_id', $request->account_id)
                    ->first();

                if ($isMemberWorkflow != null) {
                    if ($task->stage->expired_after_hours) {
                        $dateTime = Carbon::now();
                        $dateTime->addHours($task->stage->expired_after_hours);
                        $data['expired'] = $dateTime->format('Y-m-d H:i:s');
                    }
                    $data['job_assigner'] = Auth::id();
                    $data['account_id'] = $request->account_id;
                    $task->update($data);
                    $account = Account::findOrFail($request->account_id);
                    if ($request->account_id != Auth::id()) {
                        event(new NotificationEvent([
                            'full_name' => Auth::user()->full_name,
                            'task_name' => $task->name,
                            'workflow_id' => $task->stage->workflow_id,
                            'account_id' => $request->account_id,
                            'manager_id' => Auth::id(),
                        ]));
                    }
                } else {
                    return response()->json([
                        'message' => 'Bạn không phải là thành viên của workflow này'
                    ], 401);
                }
            }

            return response()->json($task);
        } else {
            return response()->json([
                'message' => 'Bạn không có quyền giao nhiệm vụ'
            ], 401);
        }
    }

    public function show(int $id)
    {
        $task = Task::query()->with(['creatorBy', 'account'])->findOrFail($id);
        $task['sticker'] = StickerTask::query()->where('task_id', $task->id)->get();
        $task['fields'] = $this->getFieldByIdTask($task->id);

        if ($task->stage_id != null) {
            $task['workflow_id'] = $task->stage->workflow_id;
        }
        return response()->json($task);
    }

    public function destroy($id)
    {
        try {
            $task = Task::query()->findOrFail($id);

            if (!$this->checkMemberWorkflow($task->stage->workflow_id)) {
                return response()->json([
                    'message' => 'Bạn không phải là thành viên của workflow này'
                ], 401);
            }

            if ($task->account_id != Auth::id() && !Auth::user()->isAdmin()) {
                return response()->json([
                    'message' => 'Bạn không có quyền xóa nhiệm vụ này'
                ], 401);
            }
            $task->delete();
            return response()->json([
                'success' => 'Xóa thành công'
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
            if ($data['items'] == null) {   
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

    private function firstStage(Task $task)
    {
        $stage = Stage::query()->where('workflow_id', $task->stage->workflow_id)
            ->orderBy('index', 'desc')
            ->first();
        $task->update([
            'stage_id' => $stage->id
        ]);
    }

    private function nextStage(Task $task)
    {
        $stage = Stage::query()->where('workflow_id', $task->stage->workflow_id)
            ->where('index', '<', $task->stage->index)
            ->orderBy('index', 'desc')
            ->first();
        // Nếu đang ở giai đoạn hoàn thành thì sẽ xóa tất cả các trường
        $data = [];
        $data = $this->updateTaskInHistory($task, $data, $stage);
        $data['stage_id'] = $stage->id;

        $task->update($data);
    }

    private function failedStage(Task $task)
    {
        $stage = Stage::query()->where('workflow_id', $task->stage->workflow_id)
            ->where('index', 0)
            ->first();
        $task->update([
            'stage_id' => $stage->id
        ]);
    }

    private function updateStatusTask($taskId, $accountId, $stageId, $status)
    {
        $status = StatusTask::create([
            'task_id' => $taskId,
            'account_id' => $accountId,
            'stage_id' => $stageId,
            'status' => $status,
        ]);
    }

    private function previousStage(Task $task)
    {
        $stage = Stage::query()->where('workflow_id', $task->stage->workflow_id)
            ->where('index', '>', $task->stage->index)
            ->orderBy('index', 'asc')
            ->first();
        $data = [];
        $data = $this->updateTaskSuccessStage($task, $data);

        $this->updateTaskInHistory($task, $data, $stage);
        $data['stage_id'] = $stage->id;

        $task->update($data);
    }

    private function updateTaskInHistory(Task $task, array $data, Stage $stage)
    {
        $worker = HistoryMoveTask::query()
            ->where('task_id', $task->id)
            ->where('old_stage', $stage->id)
            ->orderBy('id', 'desc')
            ->first() ?? null;
        if ($worker !== null) {
            $data['expired'] = $worker->expired_at;
            $data['account_id'] = $worker->worker;
            $data['started_at'] = $worker->started_at;
        } else {
            $data['expired'] = null;
            $data['account_id'] = null;
            $data['started_at'] = null;
        }

        return $data;
    }

    private function updateTaskSuccessStage(Task $task, $data)
    {
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

        return $data;
    }

    private function checkMemberWorkflow($workflow_id)
    {
        $workflow = Workflow::query()->where('id', $workflow_id)->first();
        $isMemberWorkflow = AccountWorkflow::where('workflow_id', $workflow->id)
            ->where('account_id', Auth::id())
            ->first();
        if ($isMemberWorkflow == null) {
            return false;
        }
        return true;
    }

    private function forwardTask(Task $task, int $workflow_id)
    {
        $workflow = Workflow::query()->where('id', $workflow_id)->first();
        if ($workflow != null) {
            $stage = Stage::query()
                ->where('workflow_id', $workflow->id)
                ->orderBy('index', 'desc')
                ->whereNotIn('index', [0, 1])
                ->first();

            return $stage->id;
        }
    }

    private function getFieldByIdTask(int $id)
    {
        $fieldTasks = FieldTask::query()
            ->where('task_id', $id)
            ->get();

        $fields = Field::query()
            ->whereIn('id', $fieldTasks->pluck('field_id'))
            ->get();

        $arr = collect();

        foreach ($fieldTasks as $fieldTask) {
            $arrB = [];
            $field = $fields->where('id', $fieldTask->field_id)->first();
            $arrB['id'] = $field->id;
            $arrB['label'] = $field->name;
            $arrB['value'] = $fieldTask->value;
            $arrB['type'] = $field->type;
            $arrB['options'] = $field->options;
            $arrB['required'] = $field->require == 1 ? true : false;
            $arr->push($arrB);
        }

        return $arr ?? [];
    }

    private function storeFieldTask(Task $task, Request $request)
    {
        $data = [];
        foreach ($request->fields as $field) {
            $data['field_id'] = $field['id'];
            $data['task_id'] = $task->id;
            $data['value'] = $field['value'];
            $data['created_at'] = now();
            $data['updated_at'] = now();
        }
        FieldTask::insert($data);
    }

    public function updateFieldTask(Task $task, Request $request)
    {
        $data = [];
        foreach ($request->fields as $field) {
            $fieldTask = FieldTask::query()
                ->where('task_id', $task->id)
                ->where('field_id', $field['id'])
                ->update([
                    'value' => $field['value']
                ]);
        }
    }
}
