<?php

namespace App\Services\Auth;

use App\Models\User;

class TokenResponseBuilder
{
    protected const TOKEN_TYPE = 'Bearer';
    protected const DEFAULT_ACCESS_TOKEN_TTL_SECONDS = 3600;

    public function getAccessTokenTtlSeconds(): int
    {
        $sanctumMinutes = config('sanctum.expiration');

        if (is_numeric($sanctumMinutes) && (int) $sanctumMinutes > 0) {
            return (int) $sanctumMinutes * 60;
        }

        return self::DEFAULT_ACCESS_TOKEN_TTL_SECONDS;
    }

    public function getAccessTokenExpiresAt(): \DateTimeInterface
    {
        return now()->addSeconds($this->getAccessTokenTtlSeconds());
    }

    public function build(User $user, string $accessToken, string $refreshToken): array
    {
        return [
            'token_type' => self::TOKEN_TYPE,
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in' => $this->getAccessTokenTtlSeconds(),
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
            'expires_in' => $this->getAccessTokenTtlSeconds(),
        ];
    }
}
