<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;

/*
 * 管理者用の認証用ルート
 * 一般ユーザー用の認証ルートはFortifyの自動登録を使用し、
 * FortifyServiceProviderのloginView()registerView()でビューを指定している。
 */

Route::middleware('guest:web')->group(function () {
    Route::get('/admin/login', [AuthenticatedSessionController::class, 'create'])
        ->name('admin.login');

    Route::post('/admin/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:login')
        ->name('admin.login.store');
});

Route::post('/admin/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth:web')
    ->name('admin.logout');
