<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RefreshTokenRequest;
use App\Http\Resources\UserResource;
use App\Services\Auth\AuthenticationService;
use App\Services\Auth\RefreshTokenService;
use App\Traits\ApiResponse;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

#[Group('Autentikasi', 'Login, sesi web, rotasi token, dan pengelolaan profil pengguna API.', weight: 1)]
class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected AuthenticationService $authService,
        protected RefreshTokenService $refreshTokenService
    ) {}

    #[Endpoint(
        title: 'Login Web SPA (Session Cookie)',
        description: 'Memvalidasi kredensial pengguna web SPA dan menginisialisasi sesi stateful cookie.'
    )]
    public function sessionLogin(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        if (! Auth::attempt([
            'email' => $credentials['email'],
            'password' => $credentials['password'],
        ], (bool) ($credentials['remember'] ?? false))) {
            return $this->error('Email atau password tidak valid.', 422, null, 'INVALID_CREDENTIALS');
        }

        $user = $request->user();

        if ($user->deactivated_at !== null) {
            Auth::logout();
            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            return $this->error('Akun Anda sedang dinonaktifkan.', 403, null, 'USER_DEACTIVATED');
        }

        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        return $this->success(new UserResource($user), 'Login berhasil.');
    }

    #[Endpoint(
        title: 'Logout Web SPA',
        description: 'Mengakhiri sesi web SPA dan menginvaliasi session cookie.'
    )]
    public function sessionLogout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return $this->success(null, 'Logout berhasil.');
    }

    #[Endpoint(
        title: 'Login Mobile / Token Bearer',
        description: 'Memvalidasi kredensial mobile dan mengembalikan access token Sanctum beserta refresh token.'
    )]
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only(['email', 'password']);
        $deviceName = $request->input('device_name');

        try {
            $result = $this->authService->authenticate($credentials, $deviceName);
        } catch (AuthorizationException $exception) {
            return $this->error($exception->getMessage(), 403, null, 'FORBIDDEN');
        }

        if (! $result) {
            return $this->error('Invalid credentials.', 422, null, 'INVALID_CREDENTIALS');
        }

        return $this->success($result, 'Login Success');
    }

    #[Endpoint(
        title: 'Perbarui access token mobile',
        description: 'Menukar refresh token yang masih valid dengan pasangan access token baru.'
    )]
    public function refresh(RefreshTokenRequest $request): JsonResponse
    {
        $refreshToken = $request->input('refresh_token');
        $deviceName = $request->input('device_name', 'api-refresh');

        $result = $this->refreshTokenService->refresh($refreshToken, $deviceName);

        if (! $result) {
            return $this->error('Invalid or expired refresh token.', 401, null, 'UNAUTHENTICATED');
        }

        return $this->success($result, 'Token refreshed successfully');
    }

    #[Endpoint(
        title: 'Lihat data pengguna aktif',
        description: 'Mengembalikan profil, role, dan daftar permission pengguna yang saat ini login.'
    )]
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return $this->unauthorized();
        }

        return $this->success(
            new UserResource($user),
            'User data retrieved successfully'
        );
    }

    #[Endpoint(
        title: 'Logout Mobile (Revoke Token)',
        description: 'Mencabut access token aktif dan seluruh refresh token milik pengguna.'
    )]
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user) {
            $this->authService->logout($user);
        }

        return $this->success(null, 'Successfully logged out');
    }

    #[Endpoint(
        title: 'Perbarui profil',
        description: 'Memperbarui nama dan alamat email pengguna yang sedang login.'
    )]
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

    #[Endpoint(
        title: 'Ubah password',
        description: 'Mengganti password pengguna setelah password saat ini berhasil diverifikasi.'
    )]
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
