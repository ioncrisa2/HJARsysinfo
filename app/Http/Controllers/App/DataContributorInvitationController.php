<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\App\Concerns\AuthorizesPermissions;
use App\Http\Controllers\Controller;
use App\Http\Requests\DataContributor\RejectRegistrationRequest;
use App\Models\DataContributorInvite;
use App\Models\DataContributorRegistrationRequest;
use App\Models\User;
use App\Support\AppAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Response;

class DataContributorInvitationController extends Controller
{
    use AuthorizesPermissions;

    public function index(Request $request): Response
    {
        $this->authorizeInvitationAdmin($request);
        $this->expireStaleInvitations();

        $registrationRequests = DataContributorRegistrationRequest::query()
            ->with(['invite.creator:id,name,email', 'acceptedBy:id,name', 'rejectedBy:id,name'])
            ->latest('submitted_at')
            ->paginate(10, ['*'], 'requests_page')
            ->withQueryString()
            ->through(fn (DataContributorRegistrationRequest $registrationRequest): array => [
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
            ]);

        $invitations = DataContributorInvite::query()
            ->with(['creator:id,name,email', 'registrationRequest:id,invite_id,display_name,generated_email,status,submitted_at'])
            ->latest()
            ->paginate(10, ['*'], 'invitations_page')
            ->withQueryString()
            ->through(fn (DataContributorInvite $invite): array => [
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
            ]);

        return inertia('DataContributorInvitations/Index', [
            'registrationRequests' => $registrationRequests,
            'invitations' => $invitations,
            'activeTab' => in_array($request->query('tab'), ['requests', 'tokens'], true)
                ? $request->query('tab')
                : 'requests',
            'generatedInvitationUrl' => session('generated_invitation_url'),
            'generatedInvitationToken' => session('generated_invitation_token'),
            'can' => AppAccess::capabilityMap($request->user(), [
                'manage' => 'manage_data_contributor_invitations',
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeInvitationAdmin($request);

        $token = bin2hex(random_bytes(32));

        DataContributorInvite::query()->create([
            'token_hash' => DataContributorInvite::hashToken($token),
            'created_by' => $request->user()->id,
            'expires_at' => now()->addDays(7),
            'status' => DataContributorInvite::STATUS_UNUSED,
        ]);

        return redirect()
            ->route('app.data-contributor-invitations.index')
            ->with('success', 'Invitation link berhasil dibuat. Copy link sekarang karena token mentah hanya ditampilkan sekali.')
            ->with('generated_invitation_url', route('data-contributor-registration.show', $token))
            ->with('generated_invitation_token', $token);
    }

    public function destroy(Request $request, DataContributorInvite $invite): RedirectResponse
    {
        $this->authorizeInvitationAdmin($request);

        if ($invite->status !== DataContributorInvite::STATUS_UNUSED || $invite->registrationRequest()->exists()) {
            return redirect()
                ->back()
                ->with('error', 'Invitation hanya bisa dihapus jika belum digunakan.');
        }

        $invite->delete();

        return redirect()
            ->route('app.data-contributor-invitations.index', ['tab' => 'tokens'])
            ->with('success', 'Invitation yang belum digunakan berhasil dihapus.');
    }

    public function accept(Request $request, DataContributorRegistrationRequest $registrationRequest): RedirectResponse
    {
        $this->authorizeInvitationAdmin($request);

        $result = DB::transaction(function () use ($request, $registrationRequest): string {
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

            return '';
        });

        if ($result !== '') {
            return redirect()->back()->with('error', $result);
        }

        return redirect()
            ->route('app.data-contributor-invitations.index')
            ->with('success', 'Data contributor berhasil dibuat.');
    }

    public function reject(RejectRegistrationRequest $request, DataContributorRegistrationRequest $registrationRequest): RedirectResponse
    {
        $this->authorizeInvitationAdmin($request);

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

        return redirect()
            ->route('app.data-contributor-invitations.index')
            ->with('success', 'Request data contributor berhasil ditolak.');
    }

    private function authorizeInvitationAdmin(Request $request): void
    {
        $this->authorizePermission('manage_data_contributor_invitations');
        abort_unless($request->user()?->hasRole('super_admin'), 403);
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
