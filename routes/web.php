<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// 仮ルート:勤怠打刻機能・管理者勤怠一覧機能の本実装までの動作確認用
Route::middleware('auth:web')->group(function () {
    Route::get('/attendance', fn () => view('user.temp-attendance'));
    Route::get('/admin/attendance/list', fn () => view('admin.temp-attendance-list'));
});
