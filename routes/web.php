<?php

use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Route;
use GuzzleHttp\Promise;
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
    return redirect('/api/telescope');
});

// Route::get('/test', function () {
//     $apiUrl = "https://api.mail.tm/";
//     $client = new Client();
//     $tokens = [
//         "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzUxMiJ9.eyJpYXQiOjE3NDM0MDcyMjYsInJvbGVzIjpbIlJPTEVfVVNFUiJdLCJhZGRyZXNzIjoibXVha2V5aXNyZWFsQHB0Y3QubmV0IiwiaWQiOiI2N2VhMmU4Yzc4ZTVmMjQzYjIwNjhlNjciLCJtZXJjdXJlIjp7InN1YnNjcmliZSI6WyIvYWNjb3VudHMvNjdlYTJlOGM3OGU1ZjI0M2IyMDY4ZTY3Il19fQ._TjMAOEYgeC_FIQO0V7CmBRkmHBJNaiDhxBS4EHjtE2fGEDe4rAS9lYbBTmif1NTcrm59Y9_N6zZqUfP3llXQQ",
//         "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzUxMiJ9.eyJpYXQiOjE3NDM0NzMwNDYsInJvbGVzIjpbIlJPTEVfVVNFUiJdLCJhZGRyZXNzIjoibXVha2V5Y2FjdTEyMDFAcHRjdC5uZXQiLCJpZCI6IjY3ZTlmNzI4Zjk5Y2NlMzhlMjA1YjUxZiIsIm1lcmN1cmUiOnsic3Vic2NyaWJlIjpbIi9hY2NvdW50cy82N2U5ZjcyOGY5OWNjZTM4ZTIwNWI1MWYiXX19.sYGhw5M79uh_WvInSbS5zT-PUH93RnyJtORqWKxtsVsRR_4I9IhMzijCZz6LqqmabmcoZ_iZmLS3xQFW8R8InA",
//         "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzUxMiJ9.eyJpYXQiOjE3NDM0OTI1MjMsInJvbGVzIjpbIlJPTEVfVVNFUiJdLCJhZGRyZXNzIjoibXVha2V5Nzg5MDFAcHRjdC5uZXQiLCJpZCI6IjY3ZWI4MjI3ZTc1NDBiZDllMTAyY2Y2OSIsIm1lcmN1cmUiOnsic3Vic2NyaWJlIjpbIi9hY2NvdW50cy82N2ViODIyN2U3NTQwYmQ5ZTEwMmNmNjkiXX19.Dr5dV3VcWODwfLqTqzMmjNfSId2jB166a1ZgO3s-HQXmmYI-8ZvxSeNK9JM8xWovRkKVgDHx7w7rg1UZ49Jn-Q",
//         "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzUxMiJ9.eyJpYXQiOjE3NDM0OTI2NTMsInJvbGVzIjpbIlJPTEVfVVNFUiJdLCJhZGRyZXNzIjoibXVha2V5Nzc3Nzc3QHB0Y3QubmV0IiwiaWQiOiI2N2ViODA1OWU3NTQwYmQ5ZTEwMmNmM2YiLCJtZXJjdXJlIjp7InN1YnNjcmliZSI6WyIvYWNjb3VudHMvNjdlYjgwNTllNzU0MGJkOWUxMDJjZjNmIl19fQ.9D1erMo9bYm_RTSjW6k9aZ5_S_lUDWT7-i6sNllNmibGxsRKR2phlByEpqxqSjpSuQRa-WYeA-tmQG5bSCkanA"
//     ];
//     $urlGetMail = $apiUrl . "messages";
//     $promises = [];

//     foreach ($tokens as $token) {
//         // Gửi yêu cầu bất đồng bộ với mỗi token
//         $promises[] = $client->getAsync($urlGetMail, [
//             'headers' => [
//                 'Authorization' => "Bearer $token"
//             ]
//         ]);
//     }


//     $url = "https://uwkdjqmjqupevafdqnut.supabase.co/rest/v1/code";  // Thay 'users' bằng tên bảng của bạn
//     $apiKey = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InV3a2RqcW1qcXVwZXZhZmRxbnV0Iiwicm9sZSI6ImFub24iLCJpYXQiOjE3NDM0MTY4MjksImV4cCI6MjA1ODk5MjgyOX0.nW3m4wG7hSo5R-xVyTp659dAH-_IFua6CpaWk-2w9fA";  // Thay bằng API key của bạn từ Supabase

//     $data = [
//         'id' => 2,
//         'code' => '123456',
//         'type' => 'chatgpt',
//         'email' => 'nguyen.ngoc.anh.1999@gmail.com',
//         'date_time' => Carbon::now()->format('Y-m-d H:i:s'),
//     ];

//     $response = $client->request('POST', $url, [
//         'headers' => [
//             'Content-Type' => 'application/json',
//             'apikey' => $apiKey,
//             'Authorization' => "Bearer $apiKey", // Dùng Bearer token (API key)
//             'Prefer' => 'return=representation', // Để Supabase trả về bản ghi mới thêm
//         ],
//         'json' => $data,  // Dữ liệu thêm vào bảng
//     ]);

//     // Xử lý phản hồi từ API Supabase
//     $responseBody = json_decode($response->getBody()->getContents(), true);
//     return response()->json([
//         'message' => 'Row added successfully!',
//         'data' => $responseBody,
//     ]);
// });
