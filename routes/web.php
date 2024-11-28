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

Route::get('/vnpay-payment', [\App\Http\Controllers\VNPayController::class, 'createPayment']);
Route::get('/vnpay-return', [\App\Http\Controllers\VNPayController::class, 'returnPayment']);

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
