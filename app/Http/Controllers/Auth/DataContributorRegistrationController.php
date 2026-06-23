<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\DataContributor\SubmitRegistrationRequest;
use App\Models\DataContributorInvite;
use App\Models\DataContributorRegistrationRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Response;

class DataContributorRegistrationController extends Controller
{
    private const EMAIL_DOMAIN = '@kjpp-hjar.co.id';

    public function show(string $token): Response
    {
        $invite = $this->inviteFromToken($token);

        if (! $invite || ! $invite->isUsable()) {
            $invite?->markExpiredIfNeeded();

            return $this->invalidPage();
        }

        return inertia('Auth/DataContributorRegister', [
            'submitUrl' => route('data-contributor-registration.store', $token),
            'expiresAt' => $invite->expires_at?->toISOString(),
        ]);
    }

    public function store(SubmitRegistrationRequest $request, string $token): RedirectResponse
    {
        $result = DB::transaction(function () use ($request, $token): array {
            $invite = DataContributorInvite::query()
                ->where('token_hash', DataContributorInvite::hashToken($token))
                ->lockForUpdate()
                ->first();

            if (! $invite || ! $invite->isUsable()) {
                $invite?->markExpiredIfNeeded();

                return ['ok' => false];
            }

            $displayName = $request->validated('display_name');
            $email = $this->makeAvailableEmail($displayName);

            DataContributorRegistrationRequest::query()->create([
                'invite_id' => $invite->id,
                'display_name' => $displayName,
                'generated_email' => $email,
                'phone' => $request->validated('phone'),
                'password_hash' => Hash::make($request->validated('password')),
                'status' => DataContributorRegistrationRequest::STATUS_PENDING,
                'submitted_at' => now(),
            ]);

            $invite->forceFill([
                'status' => DataContributorInvite::STATUS_SUBMITTED,
                'used_at' => now(),
            ])->save();

            return ['ok' => true, 'email' => $email];
        });

        if (! $result['ok']) {
            return redirect()
                ->route('data-contributor-registration.show', $token)
                ->with('error', 'Link registrasi tidak valid, sudah dipakai, atau sudah kedaluwarsa.');
        }

        return redirect()
            ->route('data-contributor-registration.submitted')
            ->with('generated_email', $result['email']);
    }

    public function submitted(): Response
    {
        return inertia('Auth/DataContributorRegisterSubmitted', [
            'generatedEmail' => session('generated_email'),
            'loginUrl' => route('login'),
        ]);
    }

    private function inviteFromToken(string $token): ?DataContributorInvite
    {
        return DataContributorInvite::query()
            ->where('token_hash', DataContributorInvite::hashToken($token))
            ->first();
    }

    private function invalidPage(): Response
    {
        return inertia('Auth/DataContributorRegisterInvalid', [
            'message' => 'Link registrasi tidak valid, sudah digunakan, atau sudah kedaluwarsa.',
            'loginUrl' => route('login'),
        ]);
    }

    private function makeAvailableEmail(string $displayName): string
    {
        $base = $this->normalizeEmailPrefix($displayName);

        for ($suffix = 1; $suffix <= 500; $suffix++) {
            $candidate = $suffix === 1
                ? $base.self::EMAIL_DOMAIN
                : $base.$suffix.self::EMAIL_DOMAIN;

            $exists = User::query()->where('email', $candidate)->exists()
                || DataContributorRegistrationRequest::query()->where('generated_email', $candidate)->exists();

            if (! $exists) {
                return $candidate;
            }
        }

        return $base.'.'.Str::lower(Str::random(8)).self::EMAIL_DOMAIN;
    }

    private function normalizeEmailPrefix(string $displayName): string
    {
        $prefix = Str::of($displayName)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9\s.\-_]+/', '')
            ->replaceMatches('/[\s.\-_]+/', '.')
            ->trim('.')
            ->toString();

        abort_if($prefix === '', 422, 'Nama singkat tidak dapat dijadikan email login.');

        return $prefix;
    }
}
