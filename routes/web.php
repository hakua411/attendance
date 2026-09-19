<?php

use App\Http\Controllers\AdminLoginController;
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
