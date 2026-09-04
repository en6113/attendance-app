<?php

use App\Http\Controllers\AttendanceRecordController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('user.user-login');
});

// ============================================================
// 一般ユーザー用ルート
// ============================================================
Route::middleware('auth:web')->group(function () {
    Route::get('/attendance', [AttendanceRecordController::class, 'index'])->name('attendance-register');
    Route::post('/attendance', [AttendanceRecordController::class, 'store']);
});

// ============================================================
// 管理者専用ルート
// ============================================================
Route::middleware('auth:web')->group(function () {
    // 仮ルート:管理者勤怠一覧機能の本実装までの動作確認用
    Route::get('/admin/attendance/list', fn () => view('admin.temp-attendance-list'));
});
