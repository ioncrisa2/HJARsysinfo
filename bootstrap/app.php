<?php

use App\Http\Controllers\ApiStatusController;
use App\Http\Middleware\AuthenticateDataConsumer;
use App\Http\Middleware\AuthenticateIntegrationKey;
use App\Http\Middleware\CheckSystemMode;
use App\Http\Middleware\EnsureAppUser;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            // Keep status reachable when cookies, sessions, or APP_KEY fail.
            Route::get('/', ApiStatusController::class)->name('api.status');
            Route::get('/status', ApiStatusController::class);
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->statefulApi();
        $middleware->alias([
            'app.user' => EnsureAppUser::class,
            'consumer' => AuthenticateDataConsumer::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
        $middleware->web(append: [
            CheckSystemMode::class,
        ]);
        $middleware->api(append: [
            AuthenticateIntegrationKey::class,
            CheckSystemMode::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(function (Request $request): bool {
            return $request->is('api/*') || $request->expectsJson();
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            return response()->json([
                'status' => 'error',
                'code' => 'UNAUTHENTICATED',
                'message' => 'Unauthenticated.',
                'errors' => null,
            ], 401);
        });

        $exceptions->render(function (AuthorizationException $e, Request $request) {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            return response()->json([
                'status' => 'error',
                'code' => 'FORBIDDEN',
                'message' => $e->getMessage() ?: 'Forbidden.',
                'errors' => null,
            ], 403);
        });

        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            return response()->json([
                'status' => 'error',
                'code' => 'FORBIDDEN',
                'message' => $e->getMessage() ?: 'Forbidden.',
                'errors' => null,
            ], 403);
        });

        $exceptions->render(function (ValidationException $e, Request $request) {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            return response()->json([
                'status' => 'error',
                'code' => 'VALIDATION_FAILED',
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        });

        $exceptions->render(function (NotFoundHttpException|ModelNotFoundException $e, Request $request) {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            return response()->json([
                'status' => 'error',
                'code' => 'NOT_FOUND',
                'message' => $e->getMessage() ?: 'Not found',
                'errors' => null,
            ], 404);
        });

        $exceptions->render(function (TooManyRequestsHttpException $e, Request $request) {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            return response()->json([
                'status' => 'error',
                'code' => 'RATE_LIMITED',
                'message' => $e->getMessage() ?: 'Too many requests.',
                'errors' => null,
            ], 429, $e->getHeaders());
        });

        $exceptions->render(function (HttpException $e, Request $request) {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            $code = match ($e->getStatusCode()) {
                409 => 'CONFLICT',
                410 => 'GONE',
                419 => 'CSRF_MISMATCH',
                503 => 'SERVICE_UNAVAILABLE',
                default => 'HTTP_ERROR',
            };

            return response()->json([
                'status' => 'error',
                'code' => $code,
                'message' => $e->getMessage() ?: 'Error occurred.',
                'errors' => null,
            ], $e->getStatusCode(), $e->getHeaders());
        });
    })->create();
