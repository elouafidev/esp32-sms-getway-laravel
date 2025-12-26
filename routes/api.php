<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\OutboxController;
use App\Http\Controllers\Api\InboxController;
use App\Http\Controllers\Api\HeartbeatController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/devices/{deviceUid}/outbox', [OutboxController::class, 'index']);
    Route::post('/devices/{deviceUid}/outbox/{smsId}/result', [OutboxController::class, 'result']);
    Route::post('/devices/{deviceUid}/inbox', [InboxController::class, 'store']);
    Route::post('/devices/{deviceUid}/heartbeat', [HeartbeatController::class, 'store']);
});
