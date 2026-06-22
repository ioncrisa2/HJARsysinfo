<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RefreshTokenRequest;
use App\Http\Resources\UserResource;
use App\Services\Auth\AuthenticationService;
use App\Services\Auth\RefreshTokenService;
use App\Traits\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

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

        try {
            $result = $this->authService->authenticate($credentials, $deviceName);
        } catch (AuthorizationException $exception) {
            return $this->error($exception->getMessage(), 403);
        }

        if (! $result) {
            return $this->error('Invalid credentials.', 422);
        }

        return $this->success($result, 'Login Success');
    }

    public function refresh(RefreshTokenRequest $request): JsonResponse
    {
        $refreshToken = $request->input('refresh_token');
        $deviceName = $request->input('device_name', 'api-refresh');

        $result = $this->refreshTokenService->refresh($refreshToken, $deviceName);

        if (! $result) {
            return $this->error('Invalid or expired refresh token.', 401);
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

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
        ]);

        $user->update($data);

        return $this->success(
            new UserResource($user),
            'Profile updated successfully'
        );
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->update([
            'password' => Hash::make($data['password']),
        ]);

        return $this->success(
            null,
            'Password updated successfully'
        );
    }
}
