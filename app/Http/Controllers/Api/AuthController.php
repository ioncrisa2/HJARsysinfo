<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RefreshToken;
use App\Models\User;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    use ApiResponse;

    public function login(Request $request)
    {
        $data = $request->validate([
            'email'       => ['required', 'email'],
            'password'    => ['required', 'string'],
            'device_name' => ['required', 'string'],
        ]);

        /** @var \App\Models\User|null $user */
        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 422);
        }

        // Access token Sanctum (dipakai di header Authorization: Bearer ...)
        $accessToken = $user->createToken($data['device_name'])->plainTextToken;

        // Refresh token: kita generate random string, simpan hash-nya
        $plainRefreshToken = Str::random(64);

        RefreshToken::create([
            'user_id'    => $user->id,
            'token_hash' => hash('sha256', $plainRefreshToken),
            'expires_at' => now()->addDays(30), // refresh token valid 30 hari
        ]);

        $response = [
            'token_type'    => 'Bearer',
            'access_token'  => $accessToken,
            'refresh_token' => $plainRefreshToken,
            // ini info TTL "logis" untuk client, Sanctum sendiri default tidak enforce expiry.
            'expires_in'    => 3600, // misal 1 jam (silakan atur di sisi client / middleware nanti)
            'user' => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
            ],
        ];

        return $this->success($response,'Login Success',200);
    }

    public function refresh(Request $request)
    {
        $data = $request->validate([
            'refresh_token' => ['required', 'string'],
            'device_name'   => ['nullable', 'string'],
        ]);

        $hash = hash('sha256', $data['refresh_token']);

        /** @var RefreshToken|null $stored */
        $stored = RefreshToken::where('token_hash', $hash)
            ->where('revoked', false)
            ->first();

        if (! $stored) {
            return response()->json([
                'message' => 'Invalid refresh token.',
            ], 401);
        }

        if ($stored->expires_at && $stored->expires_at->isPast()) {
            return response()->json([
                'message' => 'Refresh token expired.',
            ], 401);
        }

        $user = $stored->user;

        // Rotasi refresh token: revoke yang lama
        $stored->update(['revoked' => true]);

        // Buat refresh token baru
        $newPlainRefreshToken = Str::random(64);

        RefreshToken::create([
            'user_id'    => $user->id,
            'token_hash' => hash('sha256', $newPlainRefreshToken),
            'expires_at' => now()->addDays(30),
        ]);

        // Buat access token baru
        $deviceName = $data['device_name'] ?? 'api-refresh';
        $newAccessToken = $user->createToken($deviceName)->plainTextToken;

        $result = [
            'token_type'    => 'Bearer',
            'access_token'  => $newAccessToken,
            'refresh_token' => $newPlainRefreshToken,
            'expires_in'    => 3600,
        ];

        return $this->success($result,'Refresh Token',200);
    }

    public function me(Request $request)
    {
        return $this->success($request->user(),'User Data',200);
    }

    public function logout(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        // Hapus access token yang sedang dipakai
        if ($user && $user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }

        // Opsional: revoke semua refresh token user ini
        RefreshToken::where('user_id', $user->id)
            ->where('revoked', false)
            ->update(['revoked' => true]);

        return $this->success(null,'Success logout',200);
    }
}
