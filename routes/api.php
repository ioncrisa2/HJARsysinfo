<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DataPembandingController;
use App\Http\Controllers\Api\DictionaryController;
use App\Http\Middleware\ThrottleAuthAttempts;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/login',   [AuthController::class, 'login'])->middleware(ThrottleAuthAttempts::class);
    Route::post('/refresh', [AuthController::class, 'refresh']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me',     [AuthController::class, 'me']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
        Route::put('/profile/password', [AuthController::class, 'updatePassword']);
        Route::post('/logout',[AuthController::class, 'logout']);
    });
});

// Pembanding API (wajib pakai access token)
Route::middleware('auth:sanctum')
    ->prefix('v1')
    ->group(function () {
        Route::get('/dictionaries/{type}', [\App\Http\Controllers\Api\DictionaryController::class, 'index']);
        
        // Location Master Data
        Route::prefix('locations')->group(function () {
            Route::get('/provinces', [\App\Http\Controllers\Api\LocationController::class, 'provinces']);
            Route::get('/regencies', [\App\Http\Controllers\Api\LocationController::class, 'regencies']);
            Route::get('/districts', [\App\Http\Controllers\Api\LocationController::class, 'districts']);
            Route::get('/villages', [\App\Http\Controllers\Api\LocationController::class, 'villages']);
        });

        Route::get('/pembandings', [DataPembandingController::class, 'index']);
        Route::post('/pembandings', [DataPembandingController::class, 'store']);
        Route::post('/pembandings/similar', [DataPembandingController::class, 'similarByPayload']);
        Route::get('/pembandings/{id}', [DataPembandingController::class, 'show']);
        Route::post('/pembandings/{id}', [DataPembandingController::class, 'update']); // for multipart workaround
        Route::put('/pembandings/{id}', [DataPembandingController::class, 'update']);
        Route::patch('/pembandings/{id}', [DataPembandingController::class, 'update']);
        Route::delete('/pembandings/{id}', [DataPembandingController::class, 'destroy']);
        Route::get('/pembandings/{id}/history', [DataPembandingController::class, 'history']);
        Route::post('/pembandings/{id}/delete-request', [DataPembandingController::class, 'requestDelete']);
        Route::get('/pembandings/{pembanding}/similar', [DataPembandingController::class, 'similarById']);
    });
