<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\HistoryController;
use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Api\TaskStickerController;
use App\Http\Controllers\Api\WorkflowController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/
Route::post('login', [LoginController::class, 'store'])->name('api.login.store');

Route::middleware(['check.login'])->group(function () {
    // Send Email Notification
    Route::post('/send-email', [\App\Http\Controllers\Api\MailController::class, 'sendMail']);

// Account
    Route::post('accounts', [AccountController::class, 'store'])->name('api.accounts.store');
    Route::put('accounts/{id}', [AccountController::class, 'update'])->name('api.accounts.update');
    Route::get('accounts-search', [AccountController::class, 'search']);
    Route::get('accounts/{id}', [AccountController::class, 'show'])->name('api.accounts.show');
    Route::get('me', [AccountController::class, 'myAccount'])->name('api.accounts.myAccount');

// Login
// Workflows
    Route::get('workflows', [WorkflowController::class, 'index']);
    Route::put('workflows/{id}', [WorkflowController::class, 'update']);
    Route::get('workflows/{id}', [WorkflowController::class, 'show']);
    Route::post('workflows', [WorkflowController::class, 'store']);
    Route::delete('workflows/{id}', [WorkflowController::class, 'destroy']);
    Route::get('workflows-members/{id}', [WorkflowController::class, 'showMember']);

// Workflows categories
    Route::get('workflow-categories', [\App\Http\Controllers\Api\WorkflowCategoryController::class, 'index']);
    Route::post('workflow-categories', [\App\Http\Controllers\Api\WorkflowCategoryController::class, 'store']);
    Route::delete('workflow-categories/{id}', [\App\Http\Controllers\Api\WorkflowCategoryController::class, 'destroy']);

// Stage - giai đoạn
    Route::get('stages/{id}/workflow', [\App\Http\Controllers\Api\StageController::class, 'index']);
    Route::post('stages', [\App\Http\Controllers\Api\StageController::class, 'store']);
    Route::put('stages/{id}', [\App\Http\Controllers\Api\StageController::class, 'update']);
    Route::delete('stages/{id}', [\App\Http\Controllers\Api\StageController::class, 'destroy']);

// Tasks - nhiệm vụ
    Route::get('tasks/{id}/stage', [\App\Http\Controllers\Api\TaskController::class, 'index']);
    Route::post('tasks', [\App\Http\Controllers\Api\TaskController::class, 'store']);
    Route::get('tasks/{id}', [\App\Http\Controllers\Api\TaskController::class, 'show']);
    Route::put('tasks/{id}', [\App\Http\Controllers\Api\TaskController::class, 'update']);
    Route::delete('tasks/{id}', [\App\Http\Controllers\Api\TaskController::class, 'destroy']);

// Upload ảnh
    Route::post('upload-image', [\App\Http\Controllers\Api\TaskController::class, 'uploadImage']);

// Comments - Bình luận
    Route::get('comments/{id}/task', [\App\Http\Controllers\Api\CommentController::class, 'index']);
    Route::post('comments', [\App\Http\Controllers\Api\CommentController::class, 'store']);
    Route::put('comments/{id}', [\App\Http\Controllers\Api\CommentController::class, 'update']);
    Route::delete('comments/{id}', [\App\Http\Controllers\Api\CommentController::class, 'destroy']);

// Emoji - Cảm xúc
    Route::get('emojis/{id}/comment', [\App\Http\Controllers\Api\EmojiController::class, 'index']);
    Route::post('/emojis', [\App\Http\Controllers\Api\EmojiController::class, 'store']);
    Route::put('emojis/{id}', [\App\Http\Controllers\Api\EmojiController::class, 'update']);
    Route::delete('emojis/{id}', [\App\Http\Controllers\Api\EmojiController::class, 'destroy']);

// Các trường
    Route::get('fields/{id}/workflow', [\App\Http\Controllers\Api\FieldController::class, 'index']);
    Route::post('fields', [\App\Http\Controllers\Api\FieldController::class, 'store']);
    Route::put('fields/{id}', [\App\Http\Controllers\Api\FieldController::class, 'update']);
    Route::delete('fields/{id}', [\App\Http\Controllers\Api\FieldController::class, 'destroy']);

// Trường và giá trị của trường đó
    Route::get('task-fields/{id}/workflow', [\App\Http\Controllers\Api\DataFieldController::class, 'index']);
    Route::post('task-fields', [\App\Http\Controllers\Api\DataFieldController::class, 'store']);
    Route::put('task-fields/{id}', [\App\Http\Controllers\Api\DataFieldController::class, 'update']);
    Route::delete('task-fields/{id}', [\App\Http\Controllers\Api\DataFieldController::class, 'destroy']);

// Lịch sử kéo thả
    Route::get('task-histories', [HistoryController::class,'index']);

// Màu
    Route::get('colors', [\App\Http\Controllers\Api\ColorController::class, 'index']);
    Route::post('colors', [\App\Http\Controllers\Api\ColorController::class, 'store']);

// Nhãn dán
    Route::get('stickers', [\App\Http\Controllers\Api\StickerController::class, 'index']);
    Route::post('stickers', [\App\Http\Controllers\Api\StickerController::class, 'store']);
    Route::delete('stickers/{id}', [\App\Http\Controllers\Api\StickerController::class, 'destroy']);

// Sticker của từng nhiệm vụ
    Route::get('stickers/{id}/task', [TaskStickerController::class, 'index']);
    Route::post('data-stickers', [TaskStickerController::class, 'store']);
    Route::put('data-stickers/{id}', [TaskStickerController::class, 'update']);
    Route::get('data-stickers/{id}', [TaskStickerController::class, 'show']);
    Route::delete('data-stickers/{id}', [TaskStickerController::class, 'destroy']);

// Điểm danh
    Route::get('/attendances', [AttendanceController::class, 'index']);
    Route::post('/checkin', [AttendanceController::class, 'checkIn']);
    Route::post('/checkout', [AttendanceController::class, 'checkout']);

// Thời gian của nhiệm vụ trong từng giai đoạn
    Route::get('/time-stage/{idTask}', [HistoryController::class, 'timeStage']);

// Trường báo cáo
    Route::get('report-fields/{id}/workflow', [\App\Http\Controllers\Api\ReportFieldController::class, 'index']);
    Route::post('report-fields', [\App\Http\Controllers\Api\ReportFieldController::class, 'store']);
    Route::put('report-fields/{id}', [\App\Http\Controllers\Api\ReportFieldController::class, 'update']);
    Route::delete('report-fields/{id}', [\App\Http\Controllers\Api\ReportFieldController::class, 'destroy']);

// Dữ liệu của trường báo cáo
    Route::get('task-reports/{id}/stage', [\App\Http\Controllers\Api\TaskReportController::class, 'index']);
    Route::post('task-reports/{id}', [\App\Http\Controllers\Api\TaskReportController::class, 'store']);
    Route::put('task-reports/{id}', [\App\Http\Controllers\Api\TaskReportController::class, 'update']);
    Route::delete('task-reports/{id}', [\App\Http\Controllers\Api\TaskReportController::class, 'destroy']);
});
