<?php

use App\Http\Controllers\App\DashboardController;
use App\Http\Controllers\App\GeoLookupController;
use App\Http\Controllers\App\MasterDataPageController;
use App\Http\Controllers\App\DictionaryApiController;
use App\Http\Controllers\App\LocationApiController;
use App\Http\Controllers\App\PembandingExportController;
use App\Http\Controllers\App\PembandingController;
use App\Http\Controllers\App\ProfileController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/home', DashboardController::class)
        ->middleware(['app.user', 'permission:view_any_data::pembanding'])
        ->name('home.dashboard');
    Route::get('/home/pembanding/export', [PembandingExportController::class, 'byFilter'])
        ->middleware(['app.user', 'permission:view_any_data::pembanding'])
        ->name('home.pembanding.export');
    Route::get('/home/pembanding', [PembandingController::class, 'index'])
        ->middleware(['app.user', 'permission:view_any_data::pembanding'])
        ->name('home.pembanding.index');
    Route::get('/home/pembanding/create', [PembandingController::class, 'create'])
        ->middleware(['app.user', 'permission:create_data::pembanding'])
        ->name('home.pembanding.create');
    Route::post('/home/pembanding', [PembandingController::class, 'store'])
        ->middleware(['app.user', 'permission:create_data::pembanding'])
        ->name('home.pembanding.store');
    Route::get('/home/pembanding/{pembanding}/history', [PembandingController::class, 'history'])
        ->middleware(['app.user', 'permission:view_any_data::pembanding'])
        ->name('home.pembanding.history');
    Route::post('/home/pembanding/{pembanding}/delete-request', [PembandingController::class, 'requestDelete'])
        ->middleware(['app.user', 'permission:view_any_data::pembanding'])
        ->name('home.pembanding.delete-request');
    Route::get('/home/pembanding/{pembanding}', [PembandingController::class, 'show'])
        ->middleware(['app.user', 'permission:view_any_data::pembanding'])
        ->name('home.pembanding.show');
    Route::get('/home/pembanding/{pembanding}/edit', [PembandingController::class, 'edit'])
        ->middleware(['app.user', 'permission:update_data::pembanding'])
        ->name('home.pembanding.edit');
    Route::put('/home/pembanding/{pembanding}', [PembandingController::class, 'update'])
        ->middleware(['app.user', 'permission:update_data::pembanding'])
        ->name('home.pembanding.update');
    // Master Data unified page
    Route::get('/home/master-data', MasterDataPageController::class)
        ->middleware(['app.user', 'role_or_permission:surveyor|manage_master_data'])
        ->name('home.master-data');

    // Dictionaries API
    Route::prefix('/home/master-data/dictionaries')->middleware(['app.user', 'role_or_permission:surveyor|manage_master_data'])->group(function () {
        Route::get('{type}', [DictionaryApiController::class, 'index']);
        Route::post('{type}', [DictionaryApiController::class, 'store']);
        Route::post('{type}/reorder', [DictionaryApiController::class, 'reorder']);
        Route::put('{type}/{id}', [DictionaryApiController::class, 'update'])->whereNumber('id');
        Route::delete('{type}/{id}', [DictionaryApiController::class, 'destroy'])->whereNumber('id');
    });

    // Location API
    Route::prefix('/home/master-data/locations')->middleware(['app.user', 'role_or_permission:surveyor|manage_master_data'])->group(function () {
        Route::get('provinces', [LocationApiController::class, 'provinces']);
        Route::post('provinces', [LocationApiController::class, 'storeProvince']);
        Route::put('provinces/{province}', [LocationApiController::class, 'updateProvince']);
        Route::delete('provinces/{province}', [LocationApiController::class, 'deleteProvince']);

        Route::get('regencies', [LocationApiController::class, 'regencies']);
        Route::post('regencies', [LocationApiController::class, 'storeRegency']);
        Route::put('regencies/{regency}', [LocationApiController::class, 'updateRegency']);
        Route::delete('regencies/{regency}', [LocationApiController::class, 'deleteRegency']);

        Route::get('districts', [LocationApiController::class, 'districts']);
        Route::post('districts', [LocationApiController::class, 'storeDistrict']);
        Route::put('districts/{district}', [LocationApiController::class, 'updateDistrict']);
        Route::delete('districts/{district}', [LocationApiController::class, 'deleteDistrict']);

        Route::get('villages', [LocationApiController::class, 'villages']);
        Route::post('villages', [LocationApiController::class, 'storeVillage']);
        Route::put('villages/{village}', [LocationApiController::class, 'updateVillage']);
        Route::delete('villages/{village}', [LocationApiController::class, 'deleteVillage']);
    });

    Route::get('/profile', [ProfileController::class, 'show'])
        ->middleware(['auth'])
        ->name('profile.show');
    Route::put('/profile', [ProfileController::class, 'update'])
        ->middleware(['auth'])
        ->name('profile.update');

    // Dependent dropdown lookups for frontend form
    Route::get('/home/lookups/regencies', [GeoLookupController::class, 'regencies'])->middleware('app.user');
    Route::get('/home/lookups/districts', [GeoLookupController::class, 'districts'])->middleware('app.user');
    Route::get('/home/lookups/villages', [GeoLookupController::class, 'villages'])->middleware('app.user');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
