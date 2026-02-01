<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RefreshTokenRequest;
use App\Http\Resources\UserResource;
use App\Services\Auth\AuthenticationService;
use App\Services\Auth\RefreshTokenService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected AuthenticationService $authService,
        protected RefreshTokenService $refreshTokenService
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only(['email', 'password']);
        $deviceName = $request->input('device_name');

        $result = $this->authService->authenticate($credentials, $deviceName);

        if (!$result) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 422);
        }

        return $this->success($result, 'Login Success');
    }

    public function refresh(RefreshTokenRequest $request): JsonResponse
    {
        $refreshToken = $request->input('refresh_token');
        $deviceName = $request->input('device_name', 'api-refresh');

        $result = $this->refreshTokenService->refresh($refreshToken, $deviceName);

        if (!$result) {
            return response()->json([
                'message' => 'Invalid or expired refresh token.',
            ], 401);
        }

        return $this->success($result, 'Token refreshed successfully');
    }

    public function me(Request $request): JsonResponse
    {
        return $this->success(
            new UserResource($request->user()),
            'User data retrieved successfully'
        );
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return $this->success(null, 'Successfully logged out');
    }
}
