<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\AuthorizesPermissions;
use App\Http\Controllers\Controller;
use App\Http\Requests\DataContributor\RejectRegistrationRequest;
use App\Models\DataContributorInvite;
use App\Models\DataContributorRegistrationRequest;
use App\Models\User;
use App\Traits\ApiResponse;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

#[Group('Undangan Kontributor', 'Pengelolaan token undangan dan approval registrasi kontributor data.', weight: 9)]
class DataContributorInvitationController extends Controller
{
    use ApiResponse;
    use AuthorizesPermissions;

    #[Endpoint(
        title: 'Lihat daftar undangan kontributor',
        description: 'Mengembalikan daftar token undangan terpaginasi beserta status penggunaan.'
    )]
    public function index(Request $request): JsonResponse
    {
        $this->authorizeInvitationManagement();
        $this->expireStaleInvitations();

        $invitations = DataContributorInvite::query()
            ->with(['creator:id,name,email', 'registrationRequest:id,invite_id,display_name,generated_email,status,submitted_at'])
            ->latest()
            ->paginate((int) $request->integer('per_page', 10));

        $data = collect($invitations->items())->map(fn (DataContributorInvite $invite): array => [
            'id' => $invite->id,
            'token_fingerprint' => $this->tokenFingerprint($invite->token_hash),
            'status' => $invite->status,
            'expires_at' => $invite->expires_at?->toISOString(),
            'used_at' => $invite->used_at?->toISOString(),
            'created_at' => $invite->created_at?->toISOString(),
            'created_by' => $invite->creator?->name,
            'request' => $invite->registrationRequest ? [
                'display_name' => $invite->registrationRequest->display_name,
                'generated_email' => $invite->registrationRequest->generated_email,
                'status' => $invite->registrationRequest->status,
                'submitted_at' => $invite->registrationRequest->submitted_at?->toISOString(),
            ] : null,
        ])->all();

        return response()->json([
            'status' => 'success',
            'message' => 'Daftar token undangan berhasil diambil.',
            'data' => $data,
            'meta' => [
                'current_page' => $invitations->currentPage(),
                'per_page' => $invitations->perPage(),
                'from' => $invitations->firstItem(),
                'to' => $invitations->lastItem(),
                'total' => $invitations->total(),
                'last_page' => $invitations->lastPage(),
            ],
            'links' => [
                'first' => $invitations->url(1),
                'last' => $invitations->url($invitations->lastPage()),
                'prev' => $invitations->previousPageUrl(),
                'next' => $invitations->nextPageUrl(),
            ],
        ]);
    }

    #[Endpoint(
        title: 'Buat tautan undangan kontributor baru',
        description: 'Membuat token undangan pendaftaran kontributor (berlaku 7 hari). Token mentah hanya dikembalikan satu kali saat pembuatan.'
    )]
    public function store(Request $request): JsonResponse
    {
        $this->authorizeInvitationManagement();

        $token = bin2hex(random_bytes(32));

        $invite = DataContributorInvite::query()->create([
            'token_hash' => DataContributorInvite::hashToken($token),
            'created_by' => $request->user()->id,
            'expires_at' => now()->addDays(7),
            'status' => DataContributorInvite::STATUS_UNUSED,
        ]);

        return $this->success([
            'id' => $invite->id,
            'raw_token' => $token,
            'registration_url' => url("/register-contributor/{$token}"),
            'expires_at' => $invite->expires_at->toISOString(),
        ], 'Invitation link berhasil dibuat. Salin token ini karena hanya ditampilkan satu kali.', 201);
    }

    #[Endpoint(
        title: 'Hapus/Cabut token undangan',
        description: 'Menghapus token undangan yang belum pernah digunakan.'
    )]
    public function destroy(Request $request, DataContributorInvite $invite): JsonResponse
    {
        $this->authorizeInvitationManagement();

        if ($invite->status !== DataContributorInvite::STATUS_UNUSED || $invite->registrationRequest()->exists()) {
            return $this->error('Invitation hanya bisa dihapus jika belum digunakan.', 422, null, 'CANNOT_REVOKE_USED_INVITE');
        }

        $invite->delete();

        return $this->success(null, 'Invitation yang belum digunakan berhasil dihapus.');
    }

    #[Endpoint(
        title: 'Lihat daftar pengajuan registrasi kontributor',
        description: 'Mengembalikan daftar pengajuan pendaftaran dari calon kontributor data.'
    )]
    public function registrationRequests(Request $request): JsonResponse
    {
        $this->authorizeInvitationManagement();

        $registrationRequests = DataContributorRegistrationRequest::query()
            ->with(['invite.creator:id,name,email', 'acceptedBy:id,name', 'rejectedBy:id,name'])
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->latest('submitted_at')
            ->paginate((int) $request->integer('per_page', 10));

        $data = collect($registrationRequests->items())->map(fn (DataContributorRegistrationRequest $registrationRequest): array => [
            'id' => $registrationRequest->id,
            'display_name' => $registrationRequest->display_name,
            'generated_email' => $registrationRequest->generated_email,
            'phone' => $registrationRequest->phone,
            'status' => $registrationRequest->status,
            'submitted_at' => $registrationRequest->submitted_at?->toISOString(),
            'generated_by' => $registrationRequest->invite?->creator?->name,
            'accepted_at' => $registrationRequest->accepted_at?->toISOString(),
            'accepted_by' => $registrationRequest->acceptedBy?->name,
            'rejected_at' => $registrationRequest->rejected_at?->toISOString(),
            'rejected_by' => $registrationRequest->rejectedBy?->name,
            'reject_reason' => $registrationRequest->reject_reason,
        ])->all();

        return response()->json([
            'status' => 'success',
            'message' => 'Daftar pengajuan registrasi kontributor berhasil diambil.',
            'data' => $data,
            'meta' => [
                'current_page' => $registrationRequests->currentPage(),
                'per_page' => $registrationRequests->perPage(),
                'from' => $registrationRequests->firstItem(),
                'to' => $registrationRequests->lastItem(),
                'total' => $registrationRequests->total(),
                'last_page' => $registrationRequests->lastPage(),
            ],
            'links' => [
                'first' => $registrationRequests->url(1),
                'last' => $registrationRequests->url($registrationRequests->lastPage()),
                'prev' => $registrationRequests->previousPageUrl(),
                'next' => $registrationRequests->nextPageUrl(),
            ],
        ]);
    }

    #[Endpoint(
        title: 'Setujui (accept) pendaftaran kontributor',
        description: 'Menerima pengajuan registrasi, membuat akun user baru, dan memberikan role data_contributor.'
    )]
    public function accept(Request $request, DataContributorRegistrationRequest $registrationRequest): JsonResponse
    {
        $this->authorizeInvitationManagement();

        $error = DB::transaction(function () use ($request, $registrationRequest): ?string {
            $registrationRequest = DataContributorRegistrationRequest::query()
                ->with('invite')
                ->whereKey($registrationRequest->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $registrationRequest->isPending()) {
                return 'Request ini sudah diproses.';
            }

            if (User::query()->where('email', $registrationRequest->generated_email)->exists()) {
                return 'Email login sudah dipakai user lain. Request tidak bisa di-accept.';
            }

            $user = User::query()->create([
                'name' => $registrationRequest->display_name,
                'email' => $registrationRequest->generated_email,
                'password' => $registrationRequest->password_hash,
            ]);

            $user->assignRole('data_contributor');

            $registrationRequest->forceFill([
                'status' => DataContributorRegistrationRequest::STATUS_ACCEPTED,
                'accepted_at' => now(),
                'accepted_by' => $request->user()->id,
            ])->save();

            $registrationRequest->invite?->forceFill([
                'status' => DataContributorInvite::STATUS_ACCEPTED,
                'used_at' => $registrationRequest->invite->used_at ?? now(),
            ])->save();

            return null;
        });

        if ($error !== null) {
            return $this->error($error, 422, null, 'ACCEPT_FAILED');
        }

        return $this->success(null, 'Data contributor berhasil dibuat.');
    }

    #[Endpoint(
        title: 'Tolak (reject) pendaftaran kontributor',
        description: 'Menolak pengajuan pendaftaran kontributor dengan mencantumkan alasan penolakan.'
    )]
    public function reject(RejectRegistrationRequest $request, DataContributorRegistrationRequest $registrationRequest): JsonResponse
    {
        $this->authorizeInvitationManagement();

        DB::transaction(function () use ($request, $registrationRequest): void {
            $registrationRequest = DataContributorRegistrationRequest::query()
                ->with('invite')
                ->whereKey($registrationRequest->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $registrationRequest->isPending()) {
                return;
            }

            $registrationRequest->forceFill([
                'status' => DataContributorRegistrationRequest::STATUS_REJECTED,
                'rejected_at' => now(),
                'rejected_by' => $request->user()->id,
                'reject_reason' => $request->validated('reject_reason'),
            ])->save();

            $registrationRequest->invite?->forceFill([
                'status' => DataContributorInvite::STATUS_REJECTED,
                'used_at' => $registrationRequest->invite->used_at ?? now(),
            ])->save();
        });

        return $this->success(null, 'Request data contributor berhasil ditolak.');
    }

    private function authorizeInvitationManagement(): void
    {
        $this->authorizePermission('manage_data_contributor_invitations');
    }

    private function expireStaleInvitations(): void
    {
        DataContributorInvite::query()
            ->where('status', DataContributorInvite::STATUS_UNUSED)
            ->where('expires_at', '<', now())
            ->update(['status' => DataContributorInvite::STATUS_EXPIRED]);
    }

    private function tokenFingerprint(string $tokenHash): string
    {
        return substr($tokenHash, 0, 10).'...'.substr($tokenHash, -10);
    }
}
