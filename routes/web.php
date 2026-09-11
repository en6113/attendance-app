<?php

use App\Http\Controllers\Admin\AdminApplicationController;
use App\Http\Controllers\Admin\AdminAttendanceController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceRecordController;
use App\Http\Controllers\AttendanceReportController;
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

    // 勤怠一覧・詳細・修正
    Route::get('/attendance/list', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::get('/attendance/detail/{id}', [AttendanceController::class, 'show'])->name('attendance.show');
    Route::post('/attendance/detail/{id}', [AttendanceController::class, 'store']);

    // 申請一覧・詳細
    Route::get('/stamp_correction_request/list', [ApplicationController::class, 'index'])->name('application.index');
    // 申請一覧の「詳細」リンクは勤怠詳細画面（AttendanceController::show）を再利用する
    Route::get('/application/{id}', [AttendanceController::class, 'show']);

    // マイ勤怠統計レポート
    Route::get('/attendance/report', [AttendanceReportController::class, 'index'])->name('attendance.report');
});

// ============================================================
// 管理者専用ルート
// ============================================================
Route::middleware('auth:web', 'admin')->group(function () {
    // 日次勤怠一覧・詳細・直接修正
    Route::get('/admin/attendance/list', [AdminAttendanceController::class, 'index'])->name('admin.attendance.index');
    Route::get('/admin/attendance/{id}', [AdminAttendanceController::class, 'show'])->name('admin.attendance.show');
    Route::post('/admin/attendance/{id}', [AdminAttendanceController::class, 'update']);

    // スタッフ一覧・詳細（月次勤怠一覧）
    Route::get('/admin/staff/list', [StaffController::class, 'index'])->name('admin.staff.index');
    Route::get('/admin/attendance/staff/{id}', [StaffController::class, 'show'])->name('admin.staff.show');

    // 申請一覧・詳細・承認
    // 申請一覧は一般ユーザー用ルートを共用し、ApplicationController::index() 内で admin_status により表示を分岐している
    Route::get('/stamp_correction_request/approve/{attendance_correct_request_id}', [AdminApplicationController::class, 'show']);
    Route::post('/stamp_correction_request/approve/{attendance_correct_request_id}', [AdminApplicationController::class, 'update']);
});
