<?php

namespace App\Services\Auth;

use App\Models\RefreshToken;
use App\Models\User;
use Illuminate\Support\Str;

class RefreshTokenService
{
    protected const TOKEN_LENGTH = 64;
    protected const TOKEN_EXPIRY_DAYS = 30;

    public function create(int $userId): string
    {
        $plainToken = Str::random(self::TOKEN_LENGTH);

        RefreshToken::create([
            'user_id' => $userId,
            'token_hash' => $this->hash($plainToken),
            'expires_at' => now()->addDays(self::TOKEN_EXPIRY_DAYS),
        ]);

        return $plainToken;
    }

    public function refresh(string $plainToken, string $deviceName): ?array
    {
        $storedToken = $this->findValidToken($plainToken);

        if (!$storedToken) {
            return null;
        }

        $user = $storedToken->user;

        // Rotate token: revoke old, create new
        $this->revoke($storedToken);
        $newRefreshToken = $this->create($user->id);
        $newAccessToken = $user->createToken($deviceName)->plainTextToken;

        return app(TokenResponseBuilder::class)->buildRefresh($newAccessToken, $newRefreshToken);
    }

    public function findValidToken(string $plainToken): ?RefreshToken
    {
        $hash = $this->hash($plainToken);

        $token = RefreshToken::where('token_hash', $hash)
            ->where('revoked', false)
            ->first();

        if (!$token) {
            return null;
        }

        if ($this->isExpired($token)) {
            return null;
        }

        return $token;
    }

    public function revoke(RefreshToken $token): void
    {
        $token->update(['revoked' => true]);
    }

    public function revokeAllUserTokens(int $userId): void
    {
        RefreshToken::where('user_id', $userId)
            ->where('revoked', false)
            ->update(['revoked' => true]);
    }

    protected function hash(string $plainToken): string
    {
        return hash('sha256', $plainToken);
    }

    protected function isExpired(RefreshToken $token): bool
    {
        return $token->expires_at && $token->expires_at->isPast();
    }
}
