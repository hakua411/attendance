<?php

use App\Http\Controllers\Api\V1\AttendanceRecordController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Sanctumで認証されたユーザーの情報を取得する既存ルート
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// 公開API v1
Route::prefix('v1')->group(function () {
    // 読み取り系：認証不要
    Route::get('/attendance-records', [
        AttendanceRecordController::class,
        'index',
    ])->name('attendance-records.index');

    Route::get('/attendance-records/{attendanceRecord}', [
        AttendanceRecordController::class,
        'show',
    ])->name('attendance-records.show');

    // 登録・更新・削除：認証必須
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/attendance-records', [
            AttendanceRecordController::class,
            'store',
        ]);

        Route::put('/attendance-records/{attendanceRecord}', [
            AttendanceRecordController::class,
            'update',
        ]);

        Route::patch('/attendance-records/{attendanceRecord}', [
            AttendanceRecordController::class,
            'update',
        ]);

        Route::delete('/attendance-records/{attendanceRecord}', [
            AttendanceRecordController::class,
            'destroy',
        ]);
    });
});
