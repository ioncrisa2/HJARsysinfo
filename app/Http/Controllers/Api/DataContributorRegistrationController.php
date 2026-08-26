<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DataContributor\SubmitRegistrationRequest;
use App\Models\DataContributorInvite;
use App\Models\DataContributorRegistrationRequest;
use App\Models\User;
use App\Traits\ApiResponse;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

#[Group('Undangan Kontributor', 'Verifikasi token pendaftaran publik dan submisi akun kontributor.', weight: 9)]
class DataContributorRegistrationController extends Controller
{
    use ApiResponse;

    private const EMAIL_DOMAIN = '@kjpp-hjar.co.id';

    #[Endpoint(
        title: 'Verifikasi validitas token pendaftaran kontributor (Publik)',
        description: 'Mengecek apakah token undangan masih berlaku dan belum digunakan sebelum menampilkan form pendaftaran.'
    )]
    public function show(string $token): JsonResponse
    {
        $invite = $this->inviteFromToken($token);

        if (! $invite || ! $invite->isUsable()) {
            $invite?->markExpiredIfNeeded();

            return $this->success([
                'is_valid' => false,
                'valid' => false,
                'message' => 'Link registrasi tidak valid, sudah digunakan, atau sudah kedaluwarsa.',
            ], 'Token undangan tidak valid atau kedaluwarsa.');
        }

        return $this->success([
            'is_valid' => true,
            'valid' => true,
            'expires_at' => $invite->expires_at?->toISOString(),
        ], 'Token undangan valid.');
    }

    #[Endpoint(
        title: 'Submit pendaftaran kontributor baru (Publik)',
        description: 'Mendaftarkan permohonan kontributor dengan nama dan password. Sistem menghasilkan email domain otomatis.'
    )]
    public function store(SubmitRegistrationRequest $request, string $token): JsonResponse
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

            $registration = DataContributorRegistrationRequest::query()->create([
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

            return ['ok' => true, 'email' => $email, 'registration_id' => $registration->id];
        });

        if (! $result['ok']) {
            return $this->error('Link registrasi tidak valid, sudah dipakai, atau sudah kedaluwarsa.', 422, null, 'INVALID_TOKEN');
        }

        return $this->success([
            'generated_email' => $result['email'],
            'message' => 'Pendaftaran berhasil dikirim. Tunggu persetujuan admin untuk mengaktifkan akun Anda.',
        ], 'Pendaftaran kontributor berhasil dikirim.', 201);
    }

    private function inviteFromToken(string $token): ?DataContributorInvite
    {
        return DataContributorInvite::query()
            ->where('token_hash', DataContributorInvite::hashToken($token))
            ->first();
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
