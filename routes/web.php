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
    ->middleware('auth')
    ->name('attendance.create');

Route::post('/attendance', [AttendanceController::class, 'store'])
    ->middleware('auth')
    ->name('attendance.store');

Route::get('/attendance/list', [AttendanceListDetailController::class, 'index']);

Route::get('/attendance/{id}', [AttendanceListDetailController::class, 'show'])
    ->middleware('auth')
    ->name('attendance.show');

Route::get('/stamp_correction_request/list', function () {
    if ((int) auth()->user()->admin_status === 1) {
        return app(AdminCorrectionRequestController::class)->index();
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
    ->middleware('auth');

Route::post(
    '/attendance/{id}',
    [AttendanceController::class, 'correctionRequest']
)->middleware('auth');

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
