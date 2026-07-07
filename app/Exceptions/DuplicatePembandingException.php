<?php

namespace App\Exceptions;

use App\Models\Pembanding;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class DuplicatePembandingException extends RuntimeException
{
    public function __construct(public readonly Pembanding $existing)
    {
        parent::__construct($existing->trashed()
            ? "Data identik sudah ada pada record #{$existing->getKey()} yang telah dihapus. Pulihkan record tersebut, jangan membuat salinan baru."
            : "Data identik sudah tersedia pada record #{$existing->getKey()}. Gunakan menu update pada record tersebut.");
    }

    public function report(): bool
    {
        return false;
    }

    public function render(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->is('api/*') || $request->expectsJson()) {
            $canView = ! $this->existing->trashed()
                && ($request->user()?->can('view', $this->existing) ?? false);

            return response()->json([
                'status' => 'error',
                'message' => $this->getMessage(),
                'errors' => null,
                'duplicate' => [
                    'id' => $this->existing->getKey(),
                    'status' => $this->existing->trashed() ? 'deleted' : 'active',
                    'url' => $canView ? url("/api/v1/pembandings/{$this->existing->getKey()}") : null,
                ],
            ], 409);
        }

        $canView = ! $this->existing->trashed()
            && ($request->user()?->can('view', $this->existing) ?? false);
        $url = $canView
            ? url('/app/pembanding/'.$this->existing->getKey())
            : null;

        return back()
            ->withInput()
            ->withErrors(['duplicate' => $this->getMessage()])
            ->with('duplicate', [
                'id' => $this->existing->getKey(),
                'status' => $this->existing->trashed() ? 'deleted' : 'active',
                'url' => $url,
            ]);
    }
}
