<?php

namespace App\Traits;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    protected function success(mixed $data = null, string $message = 'Success', int $status = 200): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    protected function paginated(LengthAwarePaginator|Paginator $paginator, string $message = 'Data berhasil diambil.', int $status = 200): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'from' => method_exists($paginator, 'firstItem') ? $paginator->firstItem() : null,
                'to' => method_exists($paginator, 'lastItem') ? $paginator->lastItem() : null,
                'total' => method_exists($paginator, 'total') ? $paginator->total() : null,
                'last_page' => method_exists($paginator, 'lastPage') ? $paginator->lastPage() : null,
            ],
            'links' => [
                'first' => method_exists($paginator, 'url') ? $paginator->url(1) : null,
                'last' => method_exists($paginator, 'lastPage') && method_exists($paginator, 'url') ? $paginator->url($paginator->lastPage()) : null,
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
        ], $status);
    }

    protected function error(string $message = 'Error', int $status = 400, mixed $errors = null, ?string $code = null): JsonResponse
    {
        $payload = [
            'status' => 'error',
            'code' => $code ?? match ($status) {
                401 => 'UNAUTHENTICATED',
                403 => 'FORBIDDEN',
                404 => 'NOT_FOUND',
                409 => 'CONFLICT',
                422 => 'VALIDATION_FAILED',
                429 => 'RATE_LIMITED',
                default => 'ERROR',
            },
            'message' => $message,
            'errors' => $errors,
        ];

        return response()->json($payload, $status);
    }

    protected function respond(mixed $data = null, int $status = 200): JsonResponse
    {
        return response()->json($data, $status);
    }

    protected function validationError(mixed $errors, string $message = 'Validation failed'): JsonResponse
    {
        return $this->error($message, 422, $errors, 'VALIDATION_FAILED');
    }

    protected function unauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->error($message, 401, null, 'UNAUTHENTICATED');
    }

    protected function forbidden(string $message = 'Forbidden'): JsonResponse
    {
        return $this->error($message, 403, null, 'FORBIDDEN');
    }

    protected function notFound(string $message = 'Not found'): JsonResponse
    {
        return $this->error($message, 404, null, 'NOT_FOUND');
    }

    protected function conflict(string $message = 'Conflict', ?string $code = 'CONFLICT', mixed $errors = null, mixed $data = null): JsonResponse
    {
        $payload = [
            'status' => 'error',
            'code' => $code ?? 'CONFLICT',
            'message' => $message,
            'errors' => $errors,
        ];

        if ($data !== null) {
            $payload['data'] = $data;
        }

        return response()->json($payload, 409);
    }
}
