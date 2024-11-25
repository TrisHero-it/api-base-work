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
//            if ($request->account_id != null) {
//                if ($task->account_id != null && $request->account_id != $task->account_id) {
//                    return response()->json([
//                        'error' => 'Nhiệm vụ này đã có người nhận rồi'
//                    ]);
//                }
//            }
            if ($task == null) {
                return response()->json([
                    'error' => 'Sai mã code nhiệm vụ'
                ]);
            }
            if (!isset( $request->stage_id) && !isset($request->account_id) && !isset($request->expired)) {
                $b = $request->except('expired');
                $data = $request->except('description');
                if (isset($request->link_youtube)){
                    preg_match('/v=([a-zA-Z0-9_-]+)/', $request->link_youtube, $matches);
                    if (strpos($request->link_youtube, 'shorts') !== false) {
                        $aa = explode('/', $request->link_youtube);
                        $data['code_youtube'] = end($aa);
                    } else {
                        $data['code_youtube'] = $matches[1];
                    }
                }
                $text = $request->description ;
                $convertedText = $this->convertLinksToAnchors($text);
                $data['description'] = $convertedText;
                $task->update($data);
                return response()->json([
                    'success' => 'Chỉnh sửa thành công 106'
                ]);
            }

//    Chuyển giai đoạn
            $stage = Stage::query()->where('id', $request->stage_id)->first() ?? null;
            if (isset($stage)) {
                $data = $request->except('expired');
                $data['started_at'] = null;
                    $worker = HistoryMoveTask::query()->where('old_stage', $stage->id)->where('task_id', $task->id)->where('worker', '!=', null)->orderBy('id', 'desc')->first() ?? null;
                    if ($stage->index != 0 && $stage->index != 1) {
                        $data['account_id'] = $worker!=null ? $worker->worker : null;
                    }

                    if (isset($worker->expired_at)) {
                        $data['expired'] = $worker->expired_at;
                    }else {
                        $data['expired'] = null;
                }

                    if (isset($data['account_id'])) {
                        if ($data['account_id'] != null) {
                            $data['started_at'] = $worker->started_at;
                        }
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

                if ($task->stage->index == 1 && $task->stage_id != $stage->id) {
                    $data['link_youtube'] = null;
                    $data['view_count'] = 0;
                    $data['like_count'] = 0;
                    $data['comment_count'] = 0;
                }

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
                            'success' => 'Chỉnh sửa thành công 189'
                        ]);
                    } else {
                        return response()->json([
                            'error' => 'Chưa có người nhận nhiệm vụ'
                        ]);
                    }
                } else {
//  giao việc
                    if (isset($request->account_id)) {
                        $data = $request->except('expired');
                        $data['expired'] = null;
                        Notification::query()->create([
                            'title' => 'Nhiệm vụ mới cho bạn',
                            'message' => 'Nhiệm vụ <strong>' .$task->name. '</strong> được <strong>' . $acc->account_profile->full_name . '</strong> giao cho bạn',
                            'link' => 'https://work.1997.pro.vn/workflows/'.$task->stage->workflow->id,
                            'account_id'=> $request->account_id,
                        ]);
                        $data['started_at'] = now();
                    }
                    if (isset($stage->expired_after_hours) && $data['expired'] == null) {
                        $data['expired'] = now()->addHours($stage->expired_after_hours);
                    }
                    $task->update(
                        $data
                    );
                    return response()->json([
                        'success' => 'Chỉnh sửa thành công 216'
                    ]);
                }
            }else if (isset($request->account_id)){
                $data = $request;
                $data['expired']= null;
                $data['started_at'] = now();
                Notification::query()->create([
                    'title' => 'Nhiệm vụ mới cho bạn',
                    'message' => 'Nhiệm vụ <strong>' .$task->name. '</strong> được <strong>' . $acc->account_profile->full_name . '</strong> giao cho bạn',
                    'link' => 'https://work.1997.pro.vn/workflows/'.$task->stage->workflow->id,
                    'account_id'=> $request->account_id,
                ]);
                if ($task->stage->expired_after_hours != null && $data['expired'] == null) {
                    $data['expired'] = now()->addHours($task->stage->expired_after_hours);
                }
                $task->update(
                    $data
                );

                return response()->json([
                    'success' => 'Sửa thành công 241'
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

    public function completeTask() {

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
            return response()->json(['urlImage' => 'https://work.1997.pro.vn' . $imageUrl]);
        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 500);
        }
    }

    public function loadYoutube()
    {
        try {
            $tasks = Task::query()->where('link_youtube', '!=', null)->get();
            foreach ($tasks as $task) {
                $videoId = $task->code_youtube; // Thay VIDEO_ID bằng ID của video YouTube
                $apiKey = 'AIzaSyCHenqeRKYnGVIJoyETsCgXba4sQAuHGtA'; // Thay YOUR_API_KEY bằng API key của bạn

                $url = "https://www.googleapis.com/youtube/v3/videos?id={$videoId}&key={$apiKey}&part=snippet,contentDetails,statistics";

                $response = file_get_contents($url);
                $data = json_decode($response, true);
                $task->update([
                    'view_count' => $data['items'][0]['statistics']['viewCount'],
                    'like_count' => $data['items'][0]['statistics']['likeCount'],
                    'comment_count' => $data['items'][0]['statistics']['commentCount'],
                ]);
            }

            return response()->json([
                'success' => 'Cập nhập thành công'
            ]);
        }catch (\Exception $exception){
            return response()->json(['error' => $exception->getMessage()], 500);
        }
    }

    public function uploadImageBase64(Request $request) {

        $htmlString = $request->input('html'); // Assumed key is 'html'

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

    public function convertLinksToAnchors($text)
    {
        // Biểu thức chính quy tìm URL
        $pattern = '/(https?:\/\/[^\s]+)/i';

        // Thay thế URL bằng thẻ <a>
        $replacement = '<a href="$1" target="_blank">$1</a>';

        // Trả về chuỗi đã thay đổi
        return preg_replace($pattern, $replacement, $text);
    }

}
