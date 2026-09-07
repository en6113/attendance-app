<?php

use App\Http\Controllers\Admin\AdminAttendanceController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceRecordController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('user.user-login');
});

// ============================================================
// 一般ユーザー用ルート(勤怠登録・勤怠一覧・申請一覧)
// ============================================================
Route::middleware('auth:web')->group(function () {
    // 勤怠登録
    Route::get('/attendance', [AttendanceRecordController::class, 'index'])->name('attendance-register');
    Route::post('/attendance', [AttendanceRecordController::class, 'store']);

    // 勤怠一覧
    Route::get('/attendance/list', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::get('/attendance/detail/{id}', [AttendanceController::class, 'show'])->name('attendance.show');
    Route::post('/attendance/detail/{id}', [AttendanceController::class, 'store']);

    // 申請一覧
    Route::get('/stamp_correction_request/list', [ApplicationController::class, 'index'])->name('application.index');
    // 申請一覧の「詳細」リンクは勤怠詳細画面（AttendanceController::show）を再利用する
    Route::get('/application/{id}', [AttendanceController::class, 'show']);
});

// ============================================================
// 管理者専用ルート
// ============================================================
Route::middleware('auth:web', 'admin')->group(function () {
    // 日次勤怠一覧
    Route::get('/admin/attendance/list', [AdminAttendanceController::class, 'index'])->name('admin.attendance.index');
    Route::get('/admin/attendance/{id}', [AdminAttendanceController::class, 'show']);
    Route::post('/admin/attendance/{id}', [AdminAttendanceController::class, 'update']);
});
