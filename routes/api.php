<?php

use App\Http\Controllers\Api\V1\AttendanceRecordController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('v1')->group(function () {
    Route::apiResource('attendance-records', AttendanceRecordController::class)
        ->parameters(['attendance-records' => 'attendanceRecord'])
        ->only(['index', 'show']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::apiResource('attendance-records', AttendanceRecordController::class)
            ->parameters(['attendance-records' => 'attendanceRecord'])
            ->only(['store', 'update', 'destroy']);
    });
});
