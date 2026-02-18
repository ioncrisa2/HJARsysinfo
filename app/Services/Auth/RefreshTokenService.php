<?php

namespace App\Services\Auth;

use App\Models\RefreshToken;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RefreshTokenService
{
    protected const TOKEN_LENGTH = 64;
    protected const TOKEN_EXPIRY_DAYS = 30;

    public function __construct(
        protected TokenResponseBuilder $responseBuilder
    ) {}

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
        return DB::transaction(function () use ($plainToken, $deviceName) {
            $storedToken = RefreshToken::query()
                ->where('token_hash', $this->hash($plainToken))
                ->lockForUpdate()
                ->first();

            if (! $storedToken || $storedToken->revoked || $this->isExpired($storedToken)) {
                return null;
            }

            $user = $storedToken->user;

            if (! $user) {
                return null;
            }

            // Rotate token atomically: revoke old, then issue new pair.
            $this->revoke($storedToken);
            $newRefreshToken = $this->create($user->id);
            $newAccessToken = $this->createAccessToken($user, $deviceName);

            return $this->responseBuilder->buildRefresh($newAccessToken, $newRefreshToken);
        }, 3);
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

    protected function createAccessToken(User $user, string $deviceName): string
    {
        return $user
            ->createToken(
                $deviceName,
                ['*'],
                $this->responseBuilder->getAccessTokenExpiresAt()
            )
            ->plainTextToken;
    }
}
