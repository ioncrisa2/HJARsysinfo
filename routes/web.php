<?php

use App\Http\Controllers\DocsPinController;
use Illuminate\Support\Facades\Route;

Route::get('/docs/login', [DocsPinController::class, 'show'])->name('docs.login');
Route::post('/docs/login', [DocsPinController::class, 'store'])->name('docs.unlock');
Route::post('/docs/logout', [DocsPinController::class, 'destroy'])->name('docs.logout');
