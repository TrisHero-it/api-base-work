<?php

namespace App\Http\Controllers\Api;

use App\Events\HistoryMoveTaskEvent;
use App\Events\KpiEvent;
use App\Events\NotificationEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\TaskStoreRequest;
use App\Models\Account;
use App\Models\HistoryMoveTask;
use App\Models\Kpi;
use App\Models\Notification;
use App\Models\Stage;
use App\Models\StickerTask;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $tasks = Task::query()->where('stage_id', $request->stage_id)->orderBy('updated_at', 'desc')->get();
        foreach ($tasks as $task) {
              $task['id'] = $task->code;
              $task['sticker'] = StickerTask::query()->where('task_id', $task->id)->get();
        }

        return response()->json($tasks);
    }

    public function store(TaskStoreRequest $request)
    {
            $account = Account::query()->where('id', $request->account_id)->first() ?? null;
            $stage = Stage::query()->where('workflow_id', $request->workflow_id)->orderByDesc('index')->first();
            if ($stage->isSuccessStage()) {
                return response()->json([
                    'errors' => 'Chưa có giai đoạn'
                ], 500);
            }
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
                        'expired' => $dateTime->addHour($stage->expired_after_hours),
                        'started_at' => $dateTime
                    ]);
                }
            }
            $task['id'] = $task->code;
            return response()->json($task);
    }

    public function update($id, TaskStoreRequest $request)
    {
        $token = explode(' ', $request->header('Authorization'));
        $token = $token[1];
        $account = Account::query()->where('remember_token', $token)->first() ?? null;
        $task = Task::query()->where('code' , $id)->first();
    if (isset($request->stage_id)) {
    //  Lấy ra thứ tự của stage maf mình muốn chuyển đến
    $stage = Stage::query()->where('id', $request->stage_id)->first();
    }
        // Cập nhập thông tin nhiệm vụ
        $data = $request->all();
        if (isset($request->link_youtube)){
//          Nếu có link youtube thì lấy ra mã code của link đó
            preg_match('/v=([a-zA-Z0-9_-]+)/', $request->link_youtube, $matches);
//          Phân biệt youtube shorts
            if (strpos($request->link_youtube, 'shorts') !== false) {
                $aa = explode('/', $request->link_youtube);
                $data['code_youtube'] = end($aa);
            } else {
                $data['code_youtube'] = $matches[1];
            }
        }

// Nếu có tồn tại account_id thì là giao việc cho người khác thì thêm thông báo
            if ($task->account_id != $request->account_id && $request->account_id != null) {
                $data['started_at'] = now();
                if($task->stage->expired_after_hours != null) {
                    $data['expired'] = now()->addHours($task->stage->expired_after_hours);
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
                $data['view_count'] = 0;
                $data['like_count'] = 0;
                $data['comment_count'] = 0;
                $data['date_posted'] = null;
            }

//  Chuyển đến giai đọan hoàn thành phải có người làm mới chuyển được
            if ($stage->isSuccessStage()) {
                if ($task->account_id == null) {
                    return response()->json([
                        'errors' => 'Nhiệm vụ chưa được giao'
                    ], 500);
                }
            }

//  Lấy thông tin từ bảng kéo thả nhiệm vụ để hiển thị lại người nhận nhiệm vụ ở giai đoạn cũ
            $worker = HistoryMoveTask::query()->where('task_id', $task->id)->where('old_stage', $request->stage_id)->orderBy('id', 'desc')->first() ?? null;
            if ($worker !== null) {
                $data['expired'] = $worker->expired_at ;
                $data['account_id'] = $worker->worker;
                $data['started_at'] = $worker->started_at;
            }else {
                $data['expired'] = null;
                $data['account_id'] = null;
                $data['started_at'] = null;
            }

//  Nếu giai đoạn có hạn thì nhiệm vụ sẽ ăn theo hạn của giai đoạn
            if (isset($stage->expired_after_hours) && $data['expired'] === null && $data['account_id'] !== null) {
                $data['expired'] = now()->addHours($stage->expired_after_hours);
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

//      nếu như là chuyển tiếp giao đoạn thì thêm cho 1 kpi
            if ($task->isNextStage($stage->index) && $task->account_id != null && !$stage->isFailStage()) {
                event(new KpiEvent([
                    'account_id' => $task->account_id,
                    'task_id' => $task->id,
                    'stage_id' => $task->stage_id,
                    'status' => 0
                ]));
            } else {
               $kpi = Kpi::query()->where('task_id', $task->id)->where('stage_id', $request->stage_id)->first() ?? null;
               if ($kpi !== null) {
                   $kpi->delete();
               }
            };
        }

        $task->update($data);

        return $task;
    }

    public function show(int $id)
    {
        $task = Task::query()->where('code', $id)->first();
        $task['sticker'] = StickerTask::query()->where('task_id', $task->id)->get();

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

    public function loadYoutube(Request $request)
    {
            $a = [];
            $tasks = Task::query()->where('link_youtube', '!=', null)->whereMonth('updated_at', date('m'));
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

    public function imageBase64(Request $request) {

        $htmlString = $request->input('image'); // Assumed key is 'html'

        // Sử dụng regex để tìm dữ liệu base64 trong thẻ <img>
        preg_match('/<img.*?src=[\'"](data:image\/.*?;base64,.*?)[\'"].*?>/i', $htmlString, $matches);

        if (isset($matches[1])) {
            $base64Image = $matches[1]; // Lấy dữ liệu base64

            // Kiểm tra và giải mã dữ liệu base64
            if (preg_match('/^data:image\/(\w+);base64,/', $base64Image, $type)) {
                $base64Image = substr($base64Image, strpos($base64Image, ',') + 1);
                $type = strtolower($type[1]); // Lấy định dạng ảnh (jpg, png, gif, etc.)

                if (!in_array($type, ['jpg', 'jpeg', 'png', 'gif'])) {
                    return response()->json(['error' => 'Invalid image type'], 400);
                }

                $base64Image = base64_decode($base64Image);

                if ($base64Image === false) {
                    return response()->json(['error' => 'Base64 decode failed'], 400);
                }
            } else {
                return response()->json(['error' => 'Invalid Base64 string'], 400);
            }

            // Tạo tên file duy nhất
            $fileName = uniqid() . '.' . $type;

            // Lưu file vào thư mục 'public/images'
            $path = 'base64-images/' . $fileName;
            Storage::disk('public')->put($path, $base64Image);

            // Tạo URL dẫn đến file
            $url = Storage::url($path);

            // Tạo thẻ <img> mới
            $imgTag = "<img src=".env('APP_URL')."{$url}' alt='Uploaded Image' />";

            return response()->json(['urlImage' => $imgTag], 200);
        }

        return response()->json(['error' => 'No Base64 image found'], 400);

    }


}
