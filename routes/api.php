<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\HistoryController;
use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\TaskStickerController;
use App\Http\Controllers\Api\WorkflowController;
use Illuminate\Support\Facades\Route;

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
Route::post('login', [LoginController::class, 'store'])->name('api.login.store'); //đã validate
Route::post('accounts', [AccountController::class, 'store'])->name('api.accounts.store'); //đã validate

// Upload ảnh
Route::post('upload-image', [\App\Http\Controllers\Api\TaskController::class, 'uploadImage']); //đã validate

Route::middleware(['check.login'])->group(function () {
    Route::put('load-youtube', [\App\Http\Controllers\Api\TaskController::class, 'loadYoutube']); // không cập nhật dữ liệu

    // Route::post('upload-image-base64', [\App\Http\Controllers\Api\TaskController::class, 'uploadImageBase64']);
// Send Email Notification
//    Route::post('/send-email', [\App\Http\Controllers\Api\MailController::class, 'sendMail']);
    Route::get('/notifications', [NotificationController::class, 'index'])->name('api.notifications.index');  // không cập nhật dữ liệu

// Account
    Route::put('accounts/{id}', [AccountController::class, 'update'])->name('api.accounts.update'); //Đã validate
    Route::get('accounts-search', [AccountController::class, 'search']); // không cập nhật dữ liệu
    Route::get('accounts/{id}', [AccountController::class, 'show'])->name('api.accounts.show'); // không cập nhật dữ liệu
    Route::get('me', [AccountController::class, 'myAccount'])->name('api.accounts.myAccount');// không cập nhật dữ liệu

// Workflows
    Route::get('workflows', [WorkflowController::class, 'index']); // không cập nhật dữ liệu
    Route::put('workflows/{id}', [WorkflowController::class, 'update']); //Đã validate
    Route::get('workflows/{id}', [WorkflowController::class, 'show']);// không cập nhật dữ liệu
    Route::post('workflows', [WorkflowController::class, 'store']); // Đã validate
    Route::delete('workflows/{id}', [WorkflowController::class, 'destroy']);// không cập nhật dữ liệu
    Route::get('workflows-members/{id}', [WorkflowController::class, 'showMember']);// không cập nhật dữ liệu

// Workflows categories
    Route::get('workflow-categories', [\App\Http\Controllers\Api\WorkflowCategoryController::class, 'index']); // không cập nhật dữ liệu
    Route::post('workflow-categories', [\App\Http\Controllers\Api\WorkflowCategoryController::class, 'store']); // Đã validate
    Route::delete('workflow-categories/{id}', [\App\Http\Controllers\Api\WorkflowCategoryController::class, 'destroy']);// không cập nhật dữ liệu

// Stage - giai đoạn
    Route::get('stages/{id}/workflow', [\App\Http\Controllers\Api\StageController::class, 'index']);// không cập nhật dữ liệu
    Route::post('stages', [\App\Http\Controllers\Api\StageController::class, 'store']);//Đã validate
    Route::put('stages/{id}', [\App\Http\Controllers\Api\StageController::class, 'update']);
    Route::delete('stages/{id}', [\App\Http\Controllers\Api\StageController::class, 'destroy']); // không cập nhật dữ liệu

// Tasks - nhiệm vụ
    Route::get('tasks/{id}/stage', [\App\Http\Controllers\Api\TaskController::class, 'index']); // không cập nhật dữ liệu
    Route::post('tasks', [\App\Http\Controllers\Api\TaskController::class, 'store']);//Đã validate
    Route::get('tasks/{id}', [\App\Http\Controllers\Api\TaskController::class, 'show']); // không cập nhật dữ liệu
    Route::put('tasks/{id}', [\App\Http\Controllers\Api\TaskController::class, 'update']); // Mai anh nghĩa chỉ
    Route::delete('tasks/{id}', [\App\Http\Controllers\Api\TaskController::class, 'destroy']);// không cập nhật dữ liệu

// Comments - Bình luận
    Route::get('comments/{id}/task', [\App\Http\Controllers\Api\CommentController::class, 'index']); // không cập nhật dữ liệu
    Route::post('comments', [\App\Http\Controllers\Api\CommentController::class, 'store']); // Đã validate
    Route::put('comments/{id}', [\App\Http\Controllers\Api\CommentController::class, 'update']); // Đã validate
    Route::delete('comments/{id}', [\App\Http\Controllers\Api\CommentController::class, 'destroy']);// không cập nhật dữ liệu

// Các trường
    Route::get('fields/{id}/workflow', [\App\Http\Controllers\Api\FieldController::class, 'index']); // không cập nhật dữ liệu
    Route::post('fields', [\App\Http\Controllers\Api\FieldController::class, 'store']); // Đã validate
    Route::put('fields/{id}', [\App\Http\Controllers\Api\FieldController::class, 'update']); // Đã validate
    Route::delete('fields/{id}', [\App\Http\Controllers\Api\FieldController::class, 'destroy']);// không cập nhật dữ liệu

// Trường và giá trị của trường đó
    Route::get('task-fields/{id}/workflow', [\App\Http\Controllers\Api\DataFieldController::class, 'index']); // không cập nhật dữ liệu
    Route::post('task-fields', [\App\Http\Controllers\Api\DataFieldController::class, 'store']); // Đã validate
    Route::put('task-fields/{id}', [\App\Http\Controllers\Api\DataFieldController::class, 'update']); // Đã validate
    Route::delete('task-fields/{id}', [\App\Http\Controllers\Api\DataFieldController::class, 'destroy']); // không cập nhật dữ liệu

// Lịch sử kéo thả
    Route::get('task-histories', [HistoryController::class,'index']);

// Điểm danh
    Route::get('/attendances', [AttendanceController::class, 'index']); // không cập nhật dữ liệu
    Route::post('/check-in', [AttendanceController::class, 'checkIn']); // không cập nhật dữ liệu
    Route::post('/check-out', [AttendanceController::class, 'checkOut']); // không cập nhật dữ liệu

// Thời gian của nhiệm vụ trong từng giai đoạn
    Route::get('/time-stage/{idTask}', [HistoryController::class, 'timeStage']); // không cập nhật dữ liệu

// Trường báo cáo
    Route::get('report-fields/{id}/workflow', [\App\Http\Controllers\Api\ReportFieldController::class, 'index']); // không cập nhật dữ liệu
    Route::post('report-fields', [\App\Http\Controllers\Api\ReportFieldController::class, 'store']); // da validate
    Route::put('report-fields/{id}', [\App\Http\Controllers\Api\ReportFieldController::class, 'update']); //  da validate
    Route::delete('report-fields/{id}', [\App\Http\Controllers\Api\ReportFieldController::class, 'destroy']); // không cập nhật dữ liệu

// Dữ liệu của trường báo cáo
    Route::get('task-reports/{id}/stage', [\App\Http\Controllers\Api\TaskReportController::class, 'index']); // không cập nhật dữ liệu
    Route::post('task-reports/{codeTask}', [\App\Http\Controllers\Api\TaskReportController::class, 'store']); // da validate
    Route::put('task-reports/{id}', [\App\Http\Controllers\Api\TaskReportController::class, 'update']); // da validate
    Route::delete('task-reports/{id}', [\App\Http\Controllers\Api\TaskReportController::class, 'destroy']); // không cập nhật dữ liệu

//  KPI
    Route::get('kpi', [\App\Http\Controllers\Api\KpiController::class, 'index']); // không cập nhật dữ liệu

});
