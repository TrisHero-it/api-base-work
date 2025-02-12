<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\DayScheduleController;
use App\Http\Controllers\Api\FieldController;
use App\Http\Controllers\Api\FieldValueController;
use App\Http\Controllers\Api\HistoryMoveTaskController;
use App\Http\Controllers\Api\ImageController;
use App\Http\Controllers\Api\IpWifiController;
use App\Http\Controllers\Api\KpiController;
use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ReportFieldController;
use App\Http\Controllers\Api\ReportFieldValueController;
use App\Http\Controllers\Api\StageController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\WorkflowCategoryController;
use App\Http\Controllers\Api\WorkflowController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\StickerController;
use App\Http\Controllers\Api\TagValueController;
use App\Http\Controllers\Api\MyJobController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\ProposeController;
use App\Http\Controllers\Api\ProposeCategoryController;
use App\Http\Controllers\ScheduleWorkController;
use Illuminate\Support\Facades\Route;

Route::post('login', [LoginController::class, 'store'])->name('api.login.store');

Route::apiResource('accounts', AccountController::class);

Route::post('send_email', [\App\Http\Controllers\Api\EmailController::class, 'sendEmail'])->name('api.email.send');
Route::get('/test', function () {
    dd(now()->format('H'));
});

Route::middleware('auth.basic')->group(function () {
    Route::get('a', fn() => redirect('https://docs.google.com/spreadsheets/d/1vnOG_vqJipGhDy0HDCHCVk46XQknztLnwJ7lt4Uk4xg/edit'));
    Route::get('b', fn() => redirect('https://docs.google.com/spreadsheets/d/121Q0A0LhDw6G_jXbewOcW6iazt2K6nmFZP3eI7oJZes/edit'));
});

Route::middleware(['check.login'])->group(function () {
    Route::put('load-youtube', [TaskController::class, 'loadYoutube']);
    Route::apiResources([
        'day-off' => DayScheduleController::class,
        'roles' => RoleController::class,
        'images' => ImageController::class,
        'notifications' => NotificationController::class,
        'workflows' => WorkflowController::class,
        'stages' => StageController::class,
        'tasks' => TaskController::class,
        'workflow-categories' => WorkflowCategoryController::class,
        'fields' => FieldController::class,
        'field-values' => FieldValueController::class,
        'history-move-tasks' => HistoryMoveTaskController::class,
        'comments' => CommentController::class,
        'report-fields' => ReportFieldController::class,
        'report-field-values' => ReportFieldValueController::class,
        'kpis' => KpiController::class,
        'tags' => StickerController::class,
        'tag-task' => TagValueController::class,
        'schedule' => ScheduleWorkController::class,
        'my-tasks' => MyJobController::class,
        'departments' => DepartmentController::class,
        'proposes' => ProposeController::class,
        'propose-categories' => ProposeCategoryController::class,
        'attendances' => AttendanceController::class,
        'ip-wifis' => IpWifiController::class,
    ]);
    Route::get('work-time', [ScheduleWorkController::class, 'workTime']);
    Route::put('assign-work/{id}', [TaskController::class, 'assignWork']);
    Route::get('my-account', [AccountController::class, 'myAccount']);
    Route::post('/forgot-password', action: [AccountController::class, 'forgotPassword']);

    Route::put('seen-notification', [NotificationController::class, 'seenNotification']);
    Route::post('/tag-comment', [CommentController::class, 'notification']);
    Route::post('/check-in', [AttendanceController::class, 'checkIn']);
    Route::post('/check-out', [AttendanceController::class, 'checkOut']);

    Route::get('time-stage/{idTask}', [HistoryMoveTaskController::class, 'timeStage']);
});