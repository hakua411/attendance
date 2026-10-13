<?php

use App\Http\Controllers\AdminAttendanceController;
use App\Http\Controllers\AdminCorrectionRequestController;
use App\Http\Controllers\AdminLoginController;
use App\Http\Controllers\AdminStaffController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceListDetailController;
use App\Http\Controllers\CorrectionRequestController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/admin/login', function () {
    return view('admin.admin-login');
});

Route::post('/admin/login', [AdminLoginController::class, 'login']);

Route::post('/admin/logout', [
    AdminLoginController::class,
    'logout',
]);

Route::get('/attendance', [AttendanceController::class, 'create'])
    ->middleware(['auth', 'verified'])
    ->name('attendance.create');

Route::post('/attendance', [AttendanceController::class, 'store'])
    ->middleware(['auth', 'verified'])
    ->name('attendance.store');

Route::get('/attendance/list', [AttendanceListDetailController::class, 'index'])
    ->middleware(['auth', 'verified']);

Route::get('/attendance/{id}', [AttendanceListDetailController::class, 'show'])
    ->middleware(['auth', 'verified'])
    ->name('attendance.show');

Route::get('/stamp_correction_request/list', function () {
    $user = auth()->user();

    // 管理者はメール認証なしで一覧を表示できる
    if ((int) $user->admin_status === 1) {
        return app(AdminCorrectionRequestController::class)->index();
    }

    // 一般ユーザーはメール認証が必要
    if (! $user->hasVerifiedEmail()) {
        return redirect()->route('verification.notice');
    }

    return app(CorrectionRequestController::class)->index();
})
    ->middleware('auth')
    ->name('correction_requests.index');

Route::get('/stamp_correction_request/approve/{id}', [
    AdminCorrectionRequestController::class,
    'show',
])->middleware(['auth', 'admin']);

Route::post('/stamp_correction_request/approve/{id}', [
    AdminCorrectionRequestController::class,
    'approve',
])->middleware(['auth', 'admin']);

Route::get('/application/{id}', [CorrectionRequestController::class, 'show'])
    ->middleware(['auth', 'verified']);

Route::post(
    '/attendance/{id}',
    [AttendanceController::class, 'correctionRequest']
)->middleware(['auth', 'verified']);

Route::get('/admin/attendance/list', [
    AdminAttendanceController::class,
    'index',
])->middleware(['auth', 'admin']);

Route::get('/admin/attendance/{id}', [
    AdminAttendanceController::class,
    'show',
])->middleware(['auth', 'admin']);

Route::post('/admin/attendance/{id}', [
    AdminAttendanceController::class,
    'update',
])->middleware(['auth', 'admin']);

Route::get('/admin/staff/list', [
    AdminStaffController::class,
    'index',
])->middleware(['auth', 'admin']);

Route::get('/admin/attendance/staff/{id}', [
    AdminStaffController::class,
    'show',
])->middleware(['auth', 'admin']);

Route::post('/export', [
    AdminStaffController::class,
    'export',
])->middleware(['auth', 'admin']);
