<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/payment/create', [\App\Http\Controllers\VNPayController::class, 'createPayment'])->name('vnpay.create');
Route::get('/payment/return', [\App\Http\Controllers\VNPayController::class, 'returnPayment'])->name('vnpay.return');

Route::get('/get-youtube', function () {
    try {
        $videoId = 'vrcjoqeLeJI'; // Thay VIDEO_ID bằng ID của video YouTube
        $apiKey = 'AIzaSyCHenqeRKYnGVIJoyETsCgXba4sQAuHGtA'; // Thay YOUR_API_KEY bằng API key của bạn

        $url = "https://www.googleapis.com/youtube/v3/videos?id={$videoId}&key={$apiKey}&part=snippet,contentDetails,statistics";

        $response = file_get_contents($url);
        $data = json_decode($response, true);

// Hiển thị dữ liệu video
        dd($data['items'][0]['snippet']['publishedAt']);
    } catch (\Exception $e) {
        // Xử lý lỗi
        return [
            'error' => $e->getMessage(),
        ];
    }
});


//  Route::post('/send-email', [\App\Http\Controllers\Api\MailController::class, 'sendMail']);
//Route::post('accounts', [AccountController::class, 'store'])->name('api.accounts.store'); //đã validate

//// Emoji - Cảm xúc
//Route::get('emojis/{id}/comment', [\App\Http\Controllers\Api\EmojiController::class, 'index']);
//Route::post('/emojis', [\App\Http\Controllers\Api\EmojiController::class, 'store']);
//Route::put('emojis/{id}', [\App\Http\Controllers\Api\EmojiController::class, 'update']);
//Route::delete('emojis/{id}', [\App\Http\Controllers\Api\EmojiController::class, 'destroy']);
//
//
//// Màu
//Route::get('colors', [\App\Http\Controllers\Api\ColorController::class, 'index']);
//Route::post('colors', [\App\Http\Controllers\Api\ColorController::class, 'store']);
//

//
//
//// Sticker của từng nhiệm vụ
//Route::get('stickers/{id}/task', [TaskStickerController::class, 'index']); // không có biến truyền lên
//Route::post('data-stickers', [TaskStickerController::class, 'store']);
//Route::put('data-stickers/{id}', [TaskStickerController::class, 'update']);
//Route::get('data-stickers/{id}', [TaskStickerController::class, 'show']); // không có biến truyền lên
//Route::delete('data-stickers/{id}', [TaskStickerController::class, 'destroy']); // không có biến truyền lên
