<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\AffiliateController;
use App\Http\Controllers\Api\AssetAccountController;
use App\Http\Controllers\Api\AssetBrandController;
use App\Http\Controllers\Api\AssetCategoryController;
use App\Http\Controllers\Api\AssetController;
use App\Http\Controllers\Api\AttendanceAccountController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryResourceController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\ContractCategoryController;
use App\Http\Controllers\Api\ContractController;
use App\Http\Controllers\Api\DayoffAccountController;
use App\Http\Controllers\Api\DayScheduleController;
use App\Http\Controllers\Api\EmailController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\FieldController;
use App\Http\Controllers\Api\FieldValueController;
use App\Http\Controllers\Api\HistoryMoveTaskController;
use App\Http\Controllers\Api\ImageController;
use App\Http\Controllers\Api\IpWifiController;
use App\Http\Controllers\Api\JobPositionController;
use App\Http\Controllers\Api\KpiController;
use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ReportFieldController;
use App\Http\Controllers\Api\ReportFieldValueController;
use App\Http\Controllers\Api\ScheduleAccountController;
use App\Http\Controllers\Api\ScheduleWorkflowController;
use App\Http\Controllers\Api\StaffController;
use App\Http\Controllers\Api\StageController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\WorkflowCategoryController;
use App\Http\Controllers\Api\WorkflowController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\StickerController;
use App\Http\Controllers\Api\TagValueController;
use App\Http\Controllers\Api\MyJobController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\LeaveHistoryController;
use App\Http\Controllers\Api\LoginHistoryController;
use App\Http\Controllers\Api\ProposeController;
use App\Http\Controllers\Api\ProposeCategoryController;
use App\Http\Controllers\Api\ResourceController;
use App\Http\Controllers\Api\ViewController;
use App\Http\Controllers\ScheduleWorkController;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

Route::post('login', [LoginController::class, 'store']);
Route::post('/register', [AccountController::class, 'register']);
Route::post('send_email', [EmailController::class, 'sendEmail']);
Route::put('load-youtube', [TaskController::class, 'loadYoutube']);

Route::get('/test', function () {
    return request()->ip();
});

Route::get('/test2', function () {
    $response = Http::get('https://api64.ipify.org?format=json');
    return $response->json();
});

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResources([
        'accounts' => AccountController::class,
        'auth' => AuthController::class,
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
        'schedule-accounts' => ScheduleAccountController::class,
        'schedule-workflows' => ScheduleWorkflowController::class,
        'my-tasks' => MyJobController::class,
        'departments' => DepartmentController::class,
        'proposes' => ProposeController::class,
        'propose-categories' => ProposeCategoryController::class,
        'attendances' => AttendanceController::class,
        'attendance-accounts' => AttendanceAccountController::class,
        'ip-wifis' => IpWifiController::class,
        'resource-categories' => CategoryResourceController::class,
        'resources' => ResourceController::class,
        'views' => ViewController::class,
        'contract-categories' => ContractCategoryController::class,
        'contracts' => ContractController::class,
        'staffs' => StaffController::class,
        'job-positions' => JobPositionController::class,
        'assets' => AssetController::class,
        'asset-brands' => AssetBrandController::class,
        'asset-accounts' => AssetAccountController::class,
        'asset-categories' => AssetCategoryController::class,
        'leave-histories' => LeaveHistoryController::class,
        'login-histories' => LoginHistoryController::class,
        'day-off-accounts' => DayoffAccountController::class,
        'employees' => EmployeeController::class,
        'affiliates' => AffiliateController::class,
    ]);
    Route::get('/account-fields', [AccountController::class, 'accountsField']);
    Route::get('work-time', [ScheduleWorkController::class, 'workTime']);
    Route::put('assign-work/{id}', [TaskController::class, 'assignWork']);
    Route::get('my-account', [AccountController::class, 'myAccount']);
    Route::post('upload-files', [AccountController::class, 'storeFiles']);
    Route::put('seen-notification', [NotificationController::class, 'seenNotification']);
    Route::post('/tag-comment', [CommentController::class, 'notification']);
    Route::post('/check-in', [AttendanceController::class, 'checkIn']);
    Route::post('/check-out', [AttendanceController::class, 'checkOut']);
    Route::put('/disable-account/{id}', [AccountController::class, 'disableAccount']);
    Route::get('time-stage/{idTask}', [HistoryMoveTaskController::class, 'timeStage']);
});

Route::post('/check-in-out', [AttendanceController::class, 'checkInOut']);
