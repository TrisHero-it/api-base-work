<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\DayScheduleController;
use App\Http\Controllers\Api\FieldController;
use App\Http\Controllers\Api\FieldValueController;
use App\Http\Controllers\Api\HistoryMoveTaskController;
use App\Http\Controllers\Api\ImageController;
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
    $allowedIp= [
        '58.186.22.148',
        '127.0.0.1',
    ];
    dd(in_array(request()->ip(), $allowedIp));
});

Route::middleware(['check.login'])->group(function () {

    Route::put('load-youtube', [\App\Http\Controllers\Api\TaskController::class, 'loadYoutube']);

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
        'history-move-tasks'=> HistoryMoveTaskController::class,
        'comments' => CommentController::class,
        'report-fields' => ReportFieldController::class,
        'report-field-values' => ReportFieldValueController::class,
        'kpis' => KpiController::class,
        'tags'=> StickerController::class,
        'tag-task' => TagValueController::class,
        'schedule' => ScheduleWorkController::class,
        'my-tasks' => MyJobController::class,
        'departments' => DepartmentController::class,
        'proposes' => ProposeController::class,
        'propose-categories' => ProposeCategoryController::class,
    ]);
    // Nhãn dán
    Route::get('my-account', [AccountController::class, 'myAccount']);

    // Điểm danh
    Route::get('/attendances', [AttendanceController::class, 'index']);
    Route::post('/check-in', [AttendanceController::class, 'checkIn']);
    Route::post('/check-out', [AttendanceController::class, 'checkOut']);

    // Thời gian của nhiệm vụ trong từng giai đoạn
    Route::get('time-stage/{idTask}', [HistoryMoveTaskController::class, 'timeStage']);
});
