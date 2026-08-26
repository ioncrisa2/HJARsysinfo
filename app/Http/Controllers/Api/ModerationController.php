<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\AuthorizesPermissions;
use App\Http\Controllers\Controller;
use App\Models\Pembanding;
use App\Models\PembandingDeleteRequest;
use App\Support\AppAccess;
use App\Traits\ApiResponse;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

#[Group('Moderasi & Trash', 'Persetujuan permohonan penghapusan dan pemulihan/pembersihan data di trash.', weight: 10)]
class ModerationController extends Controller
{
    use ApiResponse;
    use AuthorizesPermissions;

    #[Endpoint(
        title: 'Lihat antrean moderasi dan trash',
        description: 'Mengembalikan daftar permohonan hapus yang menunggu evaluasi (tab=requests) atau data yang berada di tempat sampah (tab=trash).'
    )]
    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission('view_moderation');

        $tab = $request->query('tab', 'requests');
        $perPage = (int) $request->integer('per_page', 10);
        $search = $request->query('search');

        if ($tab === 'requests') {
            $requests = PembandingDeleteRequest::with(['pembanding.jenisListing:id,name', 'requestedBy:id,name,email', 'reviewedBy:id,name,email'])
                ->when($search, fn ($q) => $q->where('reason', 'like', "%{$search}%"))
                ->latest()
                ->paginate($perPage);

            $data = collect($requests->items())->map(fn (PembandingDeleteRequest $req): array => [
                'id' => $req->id,
                'pembanding_id' => $req->pembanding_id,
                'pembanding_alamat' => $req->pembanding?->alamat_data,
                'pembanding_harga' => $req->pembanding?->harga,
                'pembanding_listing' => $req->pembanding?->jenisListing?->name,
                'reason' => $req->reason,
                'status' => $req->status,
                'requested_by' => $req->requestedBy ? [
                    'id' => $req->requestedBy->id,
                    'name' => $req->requestedBy->name,
                    'email' => $req->requestedBy->email,
                ] : null,
                'reviewed_by' => $req->reviewedBy ? [
                    'id' => $req->reviewedBy->id,
                    'name' => $req->reviewedBy->name,
                    'email' => $req->reviewedBy->email,
                ] : null,
                'reviewed_at' => $req->reviewed_at?->toDateTimeString(),
                'review_note' => $req->review_note,
                'created_at' => $req->created_at?->toDateTimeString(),
            ])->all();

            $paginator = $requests;
        } else {
            $trashed = Pembanding::onlyTrashed()
                ->with(['deletedBy:id,name,email', 'jenisListing:id,name,badge_color'])
                ->when($search, function ($query) use ($search) {
                    $query->where('alamat_data', 'like', "%{$search}%")
                        ->orWhere('deleted_reason', 'like', "%{$search}%");
                })
                ->latest('deleted_at')
                ->paginate($perPage);

            $data = collect($trashed->items())->map(fn (Pembanding $pembanding): array => [
                'id' => $pembanding->id,
                'alamat_data' => $pembanding->alamat_data,
                'harga' => $pembanding->harga,
                'deleted_at' => $pembanding->deleted_at?->toDateTimeString(),
                'deleted_reason' => $pembanding->deleted_reason,
                'jenis_listing' => [
                    'name' => $pembanding->jenisListing?->name,
                    'badge_color' => $pembanding->jenisListing?->badge_color,
                ],
                'deleted_by' => [
                    'name' => $pembanding->deletedBy?->name,
                ],
            ])->all();

            $paginator = $trashed;
        }

        $can = AppAccess::capabilityMap($request->user(), [
            'approve' => 'approve_delete_request',
            'reject' => 'reject_delete_request',
            'restore' => 'restore_data::pembanding',
            'forceDelete' => 'force_delete_data::pembanding',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Data moderasi berhasil diambil.',
            'tab' => $tab,
            'data' => $data,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
            'can' => $can,
        ]);
    }

    #[Endpoint(
        title: 'Setujui permohonan hapus pembanding',
        description: 'Menerima permohonan penghapusan dan langsung melakukan soft-delete pada data pembanding terkait.'
    )]
    public function approve(string $id): JsonResponse
    {
        $this->authorizePermission('approve_delete_request');

        $requestObj = PembandingDeleteRequest::findOrFail($id);

        if ($requestObj->status !== PembandingDeleteRequest::STATUS_PENDING) {
            return $this->error('Permohonan hapus sudah diproses sebelumnya.', 422, null, 'ALREADY_PROCESSED');
        }

        DB::transaction(function () use ($requestObj) {
            $requestObj->update([
                'status' => PembandingDeleteRequest::STATUS_APPROVED,
                'reviewed_by_id' => auth()->id(),
                'reviewed_at' => now(),
            ]);

            $pembanding = $requestObj->pembanding()->lockForUpdate()->first();

            if ($pembanding && ! $pembanding->trashed()) {
                $pembanding->forceFill([
                    'deleted_by_id' => auth()->id(),
                    'deleted_reason' => $requestObj->reason,
                ])->save();

                $pembanding->delete();
            }
        });

        return $this->success(null, 'Permohonan hapus disetujui dan data dipindahkan ke tempat sampah.');
    }

    #[Endpoint(
        title: 'Tolak permohonan hapus pembanding',
        description: 'Menolak permohonan penghapusan dengan menyertakan catatan review dari moderator.'
    )]
    public function reject(Request $request, string $id): JsonResponse
    {
        $this->authorizePermission('reject_delete_request');

        $requestObj = PembandingDeleteRequest::findOrFail($id);
        $data = $request->validate(['review_note' => 'required|string|max:1000']);

        if ($requestObj->status !== PembandingDeleteRequest::STATUS_PENDING) {
            return $this->error('Permohonan hapus sudah diproses sebelumnya.', 422, null, 'ALREADY_PROCESSED');
        }

        $requestObj->update([
            'status' => PembandingDeleteRequest::STATUS_REJECTED,
            'reviewed_by_id' => auth()->id(),
            'reviewed_at' => now(),
            'review_note' => $data['review_note'],
        ]);

        return $this->success(null, 'Permohonan hapus berhasil ditolak.');
    }

    #[Endpoint(
        title: 'Pulihkan data pembanding dari trash',
        description: 'Mengembalikan data pembanding yang berstatus soft-deleted kembali ke daftar aktif.'
    )]
    public function restore(string $id): JsonResponse
    {
        $this->authorizePermission('restore_data::pembanding');

        $pembanding = Pembanding::onlyTrashed()->findOrFail($id);

        $pembanding->forceFill([
            'deleted_by_id' => null,
            'deleted_reason' => null,
        ])->save();

        $pembanding->restore();

        return $this->success(null, 'Data pembanding berhasil dipulihkan.');
    }

    #[Endpoint(
        title: 'Hapus permanen pembanding dari trash (force delete)',
        description: 'Menghapus data pembanding secara permanen dari database.'
    )]
    public function forceDelete(string $id): JsonResponse
    {
        $this->authorizePermission('force_delete_data::pembanding');

        $pembanding = Pembanding::onlyTrashed()->findOrFail($id);
        $pembanding->forceDelete();

        return $this->success(null, 'Data pembanding berhasil dihapus secara permanen.');
    }
}
