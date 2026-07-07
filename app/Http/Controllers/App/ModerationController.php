<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\App\Concerns\AuthorizesPermissions;
use App\Http\Controllers\Controller;
use App\Models\Pembanding;
use App\Models\PembandingDeleteRequest;
use App\Support\AppAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ModerationController extends Controller
{
    use AuthorizesPermissions;

    public function index(Request $request)
    {
        $this->authorizePermission('view_moderation');

        $tab = $request->query('tab', 'requests');

        $requests = null;
        $trashed = null;

        if ($tab === 'requests') {
            $requests = PembandingDeleteRequest::with(['pembanding', 'requestedBy', 'reviewedBy'])
                ->when($request->search, function ($query, $search) {
                    $query->where('reason', 'like', "%{$search}%");
                })
                ->latest()
                ->paginate(10)
                ->withQueryString();
        } else {
            $trashed = Pembanding::onlyTrashed()
                ->with(['deletedBy:id,name', 'jenisListing:id,name,badge_color'])
                ->when($request->search, function ($query, $search) {
                    $query->where('alamat_data', 'like', "%{$search}%")
                        ->orWhere('deleted_reason', 'like', "%{$search}%");
                })
                ->latest('deleted_at')
                ->paginate(10)
                ->withQueryString()
                ->through(fn (Pembanding $pembanding): array => [
                    'id' => $pembanding->id,
                    'alamat_data' => $pembanding->alamat_data,
                    'harga' => $pembanding->harga,
                    'deleted_at' => $pembanding->deleted_at,
                    'deleted_reason' => $pembanding->deleted_reason,
                    'jenis_listing' => [
                        'name' => $pembanding->jenisListing?->name,
                        'badge_color' => $pembanding->jenisListing?->badge_color,
                    ],
                    'deleted_by' => [
                        'name' => $pembanding->deletedBy?->name,
                    ],
                ]);
        }

        return inertia('Moderation/Index', [
            'tab' => $tab,
            'requestsPaginator' => $requests,
            'trashedPaginator' => $trashed,
            'filters' => $request->only('search', 'tab'),
            'can' => AppAccess::capabilityMap($request->user(), [
                'approve' => 'approve_delete_request',
                'reject' => 'reject_delete_request',
                'restore' => 'restore_data::pembanding',
                'forceDelete' => 'force_delete_data::pembanding',
            ]),
        ]);
    }

    public function approve($id)
    {
        $this->authorizePermission('approve_delete_request');

        $requestObj = PembandingDeleteRequest::findOrFail($id);

        if ($requestObj->status !== PembandingDeleteRequest::STATUS_PENDING) {
            return back()->with('error', 'Request already processed.');
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

        return back()->with('success', 'Delete request approved and property soft-deleted.');
    }

    public function reject(Request $http, $id)
    {
        $this->authorizePermission('reject_delete_request');

        $requestObj = PembandingDeleteRequest::findOrFail($id);
        $http->validate(['review_note' => 'required|string|max:1000']);

        if ($requestObj->status !== PembandingDeleteRequest::STATUS_PENDING) {
            return back()->with('error', 'Request already processed.');
        }

        $requestObj->update([
            'status' => PembandingDeleteRequest::STATUS_REJECTED,
            'reviewed_by_id' => auth()->id(),
            'reviewed_at' => now(),
            'review_note' => $http->review_note,
        ]);

        return back()->with('success', 'Delete request rejected.');
    }

    public function restore($id)
    {
        $this->authorizePermission('restore_data::pembanding');

        $pembanding = Pembanding::onlyTrashed()->findOrFail($id);

        $pembanding->forceFill([
            'deleted_by_id' => null,
            'deleted_reason' => null,
        ])->save();

        $pembanding->restore();

        return back()->with('success', 'Property restored successfully.');
    }

    public function forceDelete($id)
    {
        $this->authorizePermission('force_delete_data::pembanding');

        $pembanding = Pembanding::onlyTrashed()->findOrFail($id);
        $pembanding->forceDelete();

        return back()->with('success', 'Property permanently deleted.');
    }
}
