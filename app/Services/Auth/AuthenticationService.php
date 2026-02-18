<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthenticationService
{
    public function __construct(
        protected RefreshTokenService $refreshTokenService,
        protected TokenResponseBuilder $responseBuilder
    ) {}

    public function authenticate(array $credentials, string $deviceName): ?array
    {
        $user = $this->findUserByEmail($credentials['email']);

        if (!$user || !$this->verifyPassword($credentials['password'], $user->password)) {
            return null;
        }

        return $this->createAuthResponse($user, $deviceName);
    }

    public function logout(User $user): void
    {
        // Revoke current access token
        if ($user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }

        // Revoke all refresh tokens
        $this->refreshTokenService->revokeAllUserTokens($user->id);
    }

    protected function findUserByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    protected function verifyPassword(string $plainPassword, string $hashedPassword): bool
    {
        return Hash::check($plainPassword, $hashedPassword);
    }

    protected function createAuthResponse(User $user, string $deviceName): array
    {
        $accessToken = $this->createAccessToken($user, $deviceName);
        $refreshToken = $this->refreshTokenService->create($user->id);

        return $this->responseBuilder->build($user, $accessToken, $refreshToken);
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
