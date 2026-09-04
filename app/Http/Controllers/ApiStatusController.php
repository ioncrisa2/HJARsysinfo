<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Throwable;

class ApiStatusController extends Controller
{
    public function __invoke(Request $request): JsonResponse|Response
    {
        $databaseOnline = true;

        try {
            DB::connection()->select('select 1');
        } catch (Throwable) {
            $databaseOnline = false;
        }

        $status = $databaseOnline ? 'operational' : 'degraded';
        $httpStatus = $databaseOnline ? 200 : 503;
        $payload = [
            'status' => $status,
            'name' => config('app.name', 'HJAR Sysinfo API'),
            'version' => env('API_VERSION', '1.0.0'),
            'checks' => [
                'application' => 'online',
                'database' => $databaseOnline ? 'online' : 'unavailable',
            ],
            'timestamp' => now()->toIso8601String(),
            'documentation' => url('/docs/api'),
            'health' => url('/up'),
        ];

        if ($request->expectsJson()) {
            return response()->json($payload, $httpStatus);
        }

        return response()->view('api-status', $payload, $httpStatus);
    }
}
