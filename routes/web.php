<?php

use App\Http\Controllers\AdminLoginController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceListDetailController;
use App\Http\Controllers\CorrectionRequestController;
use App\Http\Controllers\LoginController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/admin/login', function () {
    return view('admin.admin-login');
});

Route::post('/admin/login', [AdminLoginController::class, 'login']);

Route::post('/login', [LoginController::class, 'login']);

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

Route::get('/stamp_correction_request/list', [CorrectionRequestController::class, 'index'])
    ->middleware('auth')
    ->name('correction_requests.index');

Route::get('/application/{id}', [CorrectionRequestController::class, 'show'])
    ->middleware('auth');

Route::post(
    '/attendance/{id}',
    [CorrectionRequestController::class, 'store']
)->middleware('auth');

Route::post(
    '/attendance/{id}',
    [AttendanceController::class, 'correctionRequest']
);
