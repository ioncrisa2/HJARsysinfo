<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'status' => 'success',
        'name' => config('app.name', 'HJAR Sysinfo API'),
        'environment' => config('app.env'),
        'version' => env('API_VERSION', '1.0.0'),
        'documentation' => url('/docs/api'),
        'health' => url('/up'),
    ]);
});
