<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DataPembandingController;
use App\Http\Controllers\Api\PembandingController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/login',   [AuthController::class, 'login']);
    Route::post('/refresh', [AuthController::class, 'refresh']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me',     [AuthController::class, 'me']);
        Route::post('/logout',[AuthController::class, 'logout']);
    });
});

// Pembanding API (wajib pakai access token)
Route::middleware('auth:sanctum')
    ->prefix('v1')
    ->group(function () {
        Route::get('/pembandings', [DataPembandingController::class, 'index']);
        Route::get('/pembandings/{id}', [DataPembandingController::class, 'show']);
        Route::get('/pembandings/{pembanding}/similar', [DataPembandingController::class, 'similarById']);
        Route::post('/pembandings/similar', [DataPembandingController::class, 'similarByPayload']);
    });
