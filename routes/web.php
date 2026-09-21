<?php

use App\Http\Controllers\AdminLoginController;
use App\Http\Controllers\AttendanceController;
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
