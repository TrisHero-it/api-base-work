<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\FieldController;
use App\Http\Controllers\Api\HistoryController;
use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ReportFieldController;
use App\Http\Controllers\Api\StageController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\TaskReportController;
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

// Upload ảnh
Route::post('upload-image', [\App\Http\Controllers\Api\TaskController::class, 'uploadImage']); //đã validate

Route::middleware(['check.login'])->group(function () {
    Route::put('load-youtube', [\App\Http\Controllers\Api\TaskController::class, 'loadYoutube']); // không cập nhật dữ liệu

// Send Email Notification
    Route::get('/notifications', [NotificationController::class, 'index'])->name('api.notifications.index');  // không cập nhật dữ liệu

// Account
    Route::apiResource('accounts', AccountController::class)->except('destroy');

// Workflows
    Route::apiResource('workflows', WorkflowController::class);


// Workflows categories
    Route::apiResource('workflow-categories', \App\Http\Controllers\Api\WorkflowCategoryController::class);

// Stage - giai đoạn
    Route::apiResource('workflows.stages', \App\Http\Controllers\Api\WorkflowStageController::class);

// Tasks - nhiệm vụ
    Route::apiResource('stages.tasks', \App\Http\Controllers\Api\StageTaskController::class);

// Comments - Bình luận
    Route::apiResource('tasks.comments', \App\Http\Controllers\Api\TaskCommentController::class);

// Các trường
    Route::apiResource('workflows.fields', \App\Http\Controllers\Api\WorkflowFieldController::class);

// Trường và giá trị của trường đó
    Route::apiResource('workflows.task-fields', \App\Http\Controllers\Api\WorkflowTaskFieldController::class);

// Lịch sử kéo thả
    Route::get('task-histories', [HistoryController::class,'index']);

// Điểm danh
    Route::get('/attendances', [AttendanceController::class, 'index']); // không có dữ liệu truyền lên
    Route::post('/check-in', [AttendanceController::class, 'checkIn']); // không có dữ liệu truyền lên
    Route::post('/check-out', [AttendanceController::class, 'checkOut']); // không có dữ liệu truyền lên

// Thời gian của nhiệm vụ trong từng giai đoạn
    Route::get('/time-stage/{idTask}', [HistoryController::class, 'timeStage']); // không cập nhật dữ liệu

// Trường báo cáo
    Route::apiResource('workflows.task-fields', \App\Http\Controllers\Api\WorkflowReportFieldController::class);

// Dữ liệu của trường báo cáo
    Route::post('task-reports/{codeTask}', [\App\Http\Controllers\Api\TaskReportController::class, 'store']); // da validate
    Route::apiResource('workflows.task-fields', \App\Http\Controllers\Api\WorkflowTaskReportController::class)->except('store');

//  KPI
    Route::get('kpi', [\App\Http\Controllers\Api\KpiController::class, 'index']); // không cập nhật dữ liệu
});
