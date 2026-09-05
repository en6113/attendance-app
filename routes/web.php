<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceRecordController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('user.user-login');
});

// ============================================================
// 一般ユーザー用ルート(勤怠登録・勤怠一覧)
// ============================================================
Route::middleware('auth:web')->group(function () {
    Route::get('/attendance', [AttendanceRecordController::class, 'index'])->name('attendance-register');
    Route::post('/attendance', [AttendanceRecordController::class, 'store']);

    Route::get('/attendance/list', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::get('/attendance/detail/{id}', [AttendanceController::class, 'show'])->name('attendance.show');
    Route::post('/attendance/detail/{id}', [AttendanceController::class, 'store']);
});

// ============================================================
// 管理者専用ルート
// ============================================================
Route::middleware('auth:web')->group(function () {
    // 仮ルート:管理者勤怠一覧機能の本実装までの動作確認用
    Route::get('/admin/attendance/list', fn () => view('admin.temp-attendance-list'));
});
