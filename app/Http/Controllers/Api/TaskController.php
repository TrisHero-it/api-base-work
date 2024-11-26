<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TaskStoreRequest;
use App\Http\Requests\UploadImageStoreRequest;
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

    public function uploadImage(UploadImageStoreRequest $request)
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
