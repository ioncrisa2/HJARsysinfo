<?php

namespace App\Http\Middleware;

use App\Models\IntegrationKey;
use App\Support\IntegrationAccess;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class AuthenticateIntegrationKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        if (! is_string($token) || ! str_starts_with($token, IntegrationAccess::PREFIX)) {
            return $next($request);
        }

        // Integration credentials always take precedence over a browser session.
        $request->setUserResolver(fn () => null);
        $key = strlen($token) === strlen(IntegrationAccess::PREFIX) + 64
            ? IntegrationKey::with('integration')->where('key_hash', hash('sha256', $token))->first()
            : null;

        if (! $key || $key->revoked_at || $key->expires_at->isPast() || ! $key->integration->is_active) {
            return $this->error(401, 'UNAUTHENTICATED', 'API key tidak valid, kedaluwarsa, atau dinonaktifkan.');
        }

        $request->attributes->set('integration_key', $key);
        $started = hrtime(true);
        $status = 500;
        try {
            $scope = IntegrationAccess::routeScope($request);
            if (! $scope || ! $key->allows($scope)) {
                $status = 403;

                return $this->error($status, 'FORBIDDEN', 'API key tidak diizinkan mengakses endpoint ini.');
            }

            $bucket = 'integration-key:'.$key->id;
            if (RateLimiter::tooManyAttempts($bucket, $key->integration->requests_per_minute)) {
                $status = 429;

                return $this->error($status, 'RATE_LIMITED', 'Batas request API key terlampaui.')
                    ->header('Retry-After', (string) RateLimiter::availableIn($bucket));
            }
            RateLimiter::hit($bucket, 60);

            $key->update(['last_used_at' => now()]);
            $response = $next($request);
            $status = $response->getStatusCode();

            return $response;
        } catch (Throwable $exception) {
            $status = match (true) {
                $exception instanceof ValidationException => 422,
                $exception instanceof ModelNotFoundException => 404,
                $exception instanceof AuthorizationException => 403,
                $exception instanceof HttpExceptionInterface => $exception->getStatusCode(),
                default => 500,
            };
            throw $exception;
        } finally {
            // Log only identifiers and the route template, never headers, URLs or bodies.
            try {
                Log::channel('integration')->info('integration_request', [
                    'integration_id' => $key->integration_id,
                    'key_id' => $key->id,
                    'method' => $request->method(),
                    'route' => $request->route()?->uri(),
                    'status' => $status,
                    'duration_ms' => round((hrtime(true) - $started) / 1e6, 2),
                ]);
            } catch (Throwable) {
                // Logging availability must not change an API response.
            }
        }
    }

    private function error(int $status, string $code, string $message): JsonResponse
    {
        return response()->json(['status' => 'error', 'code' => $code, 'message' => $message, 'errors' => null], $status);
    }
}
