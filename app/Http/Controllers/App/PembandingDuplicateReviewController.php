<?php

namespace App\Http\Controllers\App;

use App\Actions\Pembanding\SavePembandingAction;
use App\Http\Controllers\Controller;
use App\Models\Pembanding;
use App\Models\PembandingDuplicateSubmission;
use App\Services\Pembanding\PembandingComparisonService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PembandingDuplicateReviewController extends Controller
{
    public function show(
        Request $request,
        PembandingDuplicateSubmission $submission,
        PembandingComparisonService $comparison,
    ): Response {
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

        abort_if($candidates->isEmpty(), 403, 'Kandidat duplikat tidak dapat diakses oleh akun ini.');

        return Inertia::render('Pembanding/DuplicateReview', [
            'breadcrumbs' => [
                ['label' => 'Beranda', 'href' => route('app.dashboard'), 'icon' => 'pi-home'],
                ['label' => 'Data Pembanding', 'href' => route('app.pembanding.index')],
                ['label' => 'Konfirmasi Duplikasi', 'href' => null],
            ],
            'submission' => [
                'id' => $submission->id,
                'expires_at' => $submission->expires_at->toDateTimeString(),
                'image_url' => route('app.pembanding.duplicate-reviews.image', $submission),
                'rows' => $comparison->rows($submission->payload),
            ],
            'candidates' => $candidates,
        ]);
    }

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

    public function useExisting(
        Request $request,
        PembandingDuplicateSubmission $submission,
        Pembanding $pembanding,
    ): RedirectResponse {
        $this->authorizeCandidate($request, $submission, $pembanding, 'view');
        $submission->delete();

        return redirect()
            ->route('app.pembanding.show', $pembanding)
            ->with('success', 'Data baru dibatalkan. Record lama tetap digunakan.');
    }

    public function replace(
        Request $request,
        PembandingDuplicateSubmission $submission,
        Pembanding $pembanding,
        SavePembandingAction $savePembanding,
    ): RedirectResponse {
        $this->authorizeCandidate($request, $submission, $pembanding, 'update');

        $expectedVersion = $submission->candidate_versions[(string) $pembanding->id] ?? null;
        abort_if($expectedVersion !== $pembanding->updated_at?->toISOString(), 409, 'Record lama telah berubah. Muat ulang proses input dan periksa kembali.');

        $data = $submission->payload;
        unset($data['created_by']);
        $data['updated_by'] = $request->user()->id;

        $savePembanding->update($pembanding, $data, null, $submission->candidateIds());
        $submission->delete();

        return redirect()
            ->route('app.pembanding.show', $pembanding)
            ->with('success', 'Record lama berhasil diperbarui menggunakan data yang baru diinput.');
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
