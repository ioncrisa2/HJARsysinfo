<?php

namespace App\Http\Controllers\Api;

use App\Actions\Pembanding\SavePembandingAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\PembandingResource;
use App\Models\Pembanding;
use App\Models\PembandingDuplicateSubmission;
use App\Services\Pembanding\PembandingComparisonService;
use App\Traits\ApiResponse;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Group('Data Pembanding', 'Resolusi duplikasi data pembanding.', weight: 4)]
class PembandingDuplicateReviewController extends Controller
{
    use ApiResponse;

    #[Endpoint(
        title: 'Lihat detail tinjauan duplikat',
        description: 'Mengembalikan perbandingan data yang baru disubmit dengan kandidat duplikat yang sudah tersimpan di database.'
    )]
    public function show(
        Request $request,
        PembandingDuplicateSubmission $submission,
        PembandingComparisonService $comparison,
    ): JsonResponse {
        $this->authorizeSubmission($request, $submission);

        $candidates = Pembanding::withTrashed()
            ->with('creator:id,name')
            ->whereKey($submission->candidateIds())
            ->get()
            ->filter(fn (Pembanding $candidate): bool => Gate::forUser($request->user())->allows('view', $candidate))
            ->map(fn (Pembanding $candidate): array => [
                'id' => $candidate->id,
                'created_by' => $candidate->creator?->name ?? 'Tidak diketahui',
                'updated_at' => $candidate->updated_at?->toDateTimeString(),
                'deleted' => $candidate->trashed(),
                'can_update' => ! $candidate->trashed() && Gate::forUser($request->user())->allows('update', $candidate),
                'image_url' => $candidate->image_path,
                'rows' => $comparison->rows($candidate),
            ])->values();

        if ($candidates->isEmpty()) {
            return $this->error('Kandidat duplikat tidak dapat diakses oleh akun ini.', 403, null, 'FORBIDDEN');
        }

        return $this->success([
            'submission' => [
                'id' => $submission->id,
                'expires_at' => $submission->expires_at->toDateTimeString(),
                'image_url' => url("/api/v1/pembanding-submissions/{$submission->id}/image"),
                'rows' => $comparison->rows($submission->payload),
            ],
            'candidates' => $candidates,
        ], 'Data review duplikat berhasil diambil.');
    }

    #[Endpoint(
        title: 'Stream gambar submission duplikat',
        description: 'Mengalirkan binary gambar dari file staged duplicate submission.'
    )]
    public function image(Request $request, PembandingDuplicateSubmission $submission): StreamedResponse
    {
        $this->authorizeSubmission($request, $submission);
        abort_unless(Storage::disk('local')->exists($submission->image_path), 404);

        return Storage::disk('local')->response(
            $submission->image_path,
            $submission->image_original_name,
            ['Content-Type' => $submission->image_mime_type, 'Cache-Control' => 'private, no-store'],
        );
    }

    #[Endpoint(
        title: 'Resolusi tinjauan duplikat',
        description: 'Menyelesaikan konflik duplikat dengan strategi use_existing (batalkan input baru) atau replace_existing (timpa record lama).'
    )]
    public function resolve(
        Request $request,
        PembandingDuplicateSubmission $submission,
        SavePembandingAction $savePembanding,
    ): JsonResponse {
        $this->authorizeSubmission($request, $submission);

        $candidateId = $request->input('candidate_id') ?? $request->input('pembanding_id');
        if ($candidateId !== null) {
            $request->merge(['candidate_id' => $candidateId]);
        }

        $data = $request->validate([
            'strategy' => ['required', 'string', 'in:use_existing,replace_existing'],
            'candidate_id' => ['required', 'integer'],
        ]);

        $candidate = Pembanding::withTrashed()->findOrFail($data['candidate_id']);
        $this->authorizeCandidate($request, $submission, $candidate, $data['strategy'] === 'replace_existing' ? 'update' : 'view');

        if ($data['strategy'] === 'use_existing') {
            $submission->delete();

            return $this->success(
                new PembandingResource($candidate),
                'Data baru dibatalkan. Record lama tetap digunakan.'
            );
        }

        // replace_existing
        $expectedVersion = $submission->candidate_versions[(string) $candidate->id] ?? null;
        if ($expectedVersion !== $candidate->updated_at?->toISOString()) {
            return $this->error('Record lama telah berubah. Muat ulang proses input dan periksa kembali.', 409, null, 'STALE_RECORD');
        }

        $payload = $submission->payload;
        unset($payload['created_by']);
        $payload['updated_by'] = $request->user()->id;

        $savePembanding->update($candidate, $payload, null, $submission->candidateIds());
        $submission->delete();

        $candidate->refresh()->load([
            'jenisListing:id,name', 'jenisObjek:id,name', 'province:id,name',
            'regency:id,name', 'district:id,name', 'village:id,name', 'creator:id,name,email', 'updater:id,name,email',
        ]);

        return $this->success(
            new PembandingResource($candidate),
            'Record lama berhasil diperbarui menggunakan data yang baru diinput.'
        );
    }

    private function authorizeSubmission(Request $request, PembandingDuplicateSubmission $submission): void
    {
        abort_unless((int) $submission->user_id === (int) $request->user()->id, 404);

        if ($submission->isExpired()) {
            $submission->delete();
            abort(410, 'Data input sementara telah kedaluwarsa. Silakan isi ulang formulir.');
        }
    }

    private function authorizeCandidate(
        Request $request,
        PembandingDuplicateSubmission $submission,
        Pembanding $pembanding,
        string $ability,
    ): void {
        $this->authorizeSubmission($request, $submission);
        abort_unless(in_array((int) $pembanding->id, $submission->candidateIds(), true), 404);
        Gate::forUser($request->user())->authorize($ability, $pembanding);
    }
}
