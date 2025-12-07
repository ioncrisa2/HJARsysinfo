<?php

namespace App\Services\Auth;

use App\Models\User;

class TokenResponseBuilder
{
    protected const TOKEN_TYPE = 'Bearer';
    protected const ACCESS_TOKEN_TTL = 3600; // 1 hour in seconds

    public function build(User $user, string $accessToken, string $refreshToken): array
    {
        return [
            'token_type' => self::TOKEN_TYPE,
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in' => self::ACCESS_TOKEN_TTL,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ];
    }

    public function buildRefresh(string $accessToken, string $refreshToken): array
    {
        return [
            'token_type' => self::TOKEN_TYPE,
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in' => self::ACCESS_TOKEN_TTL,
        ];
    }
}
