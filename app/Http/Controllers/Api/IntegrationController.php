<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\AuthorizesPermissions;
use App\Http\Controllers\Controller;
use App\Models\Integration;
use App\Services\Auth\IntegrationKeyService;
use App\Support\IntegrationAccess;
use App\Traits\ApiResponse;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

#[Group('Integrasi', 'Pengelolaan aplikasi dan API key oleh pengguna dengan permission manage_integrations.')]
class IntegrationController extends Controller
{
    use ApiResponse;
    use AuthorizesPermissions;

    #[Endpoint(title: 'Daftar integrasi', description: 'Daftar aplikasi dan metadata key. Nilai rahasia serta hash key tidak disertakan.')]
    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission('manage_integrations');
        $data = $request->validate(['per_page' => ['nullable', 'integer', 'min:1', 'max:100']]);

        return $this->paginated(Integration::withCount('keys')->latest('id')->paginate($data['per_page'] ?? 25));
    }

    #[Endpoint(title: 'Daftar cakupan izin integrasi')]
    public function scopes(): JsonResponse
    {
        $this->authorizePermission('manage_integrations');

        return $this->success(IntegrationAccess::SCOPES);
    }

    #[Endpoint(title: 'Buat integrasi')]
    public function store(Request $request): JsonResponse
    {
        $this->authorizePermission('manage_integrations');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'requests_per_minute' => ['sometimes', 'integer', 'min:1', 'max:600'],
        ]);
        $integration = DB::transaction(function () use ($data, $request) {
            $integration = Integration::create([...$data, 'created_by' => $request->user()->id]);
            activity('integrations')->causedBy($request->user())->performedOn($integration)->log('integration_created');

            return $integration->refresh();
        });

        return $this->success($integration, 'Integrasi dibuat.', 201);
    }

    #[Endpoint(title: 'Detail integrasi', description: 'Metadata aplikasi dan seluruh key, tanpa nilai rahasia.')]
    public function show(Integration $integration): JsonResponse
    {
        $this->authorizePermission('manage_integrations');

        return $this->success($integration->load('keys'));
    }

    #[Endpoint(title: 'Ubah integrasi', description: 'is_active=false langsung menonaktifkan akses seluruh key aplikasi.')]
    public function update(Request $request, Integration $integration): JsonResponse
    {
        $this->authorizePermission('manage_integrations');
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'is_active' => ['sometimes', 'required', 'boolean'],
            'requests_per_minute' => ['sometimes', 'required', 'integer', 'min:1', 'max:600'],
        ]);
        DB::transaction(function () use ($integration, $data, $request) {
            $integration->update($data);
            activity('integrations')->causedBy($request->user())->performedOn($integration)
                ->withProperties($data)->log('integration_updated');
        });

        return $this->success($integration, 'Integrasi diperbarui.');
    }

    #[Endpoint(title: 'Terbitkan API key', description: 'plain_text_key hanya dikembalikan sekali. Simpan sebagai secret di backend aplikasi konsumen. Terbitkan key baru untuk rotasi, lalu cabut key lama setelah migrasi.')]
    public function issueKey(Request $request, Integration $integration, IntegrationKeyService $service): JsonResponse
    {
        $this->authorizePermission('manage_integrations');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'scopes' => ['required', 'array', 'min:1', 'max:4'],
            'scopes.*' => ['required', 'string', 'distinct', Rule::in(IntegrationAccess::SCOPES)],
            'expires_at' => ['required', 'date', 'after:now', 'before_or_equal:'.now()->addYear()->toIso8601String()],
        ]);

        return $this->success($service->issue($integration, $request->user(), $data), 'Simpan key ini; tidak dapat ditampilkan kembali.', 201)
            ->header('Cache-Control', 'no-store, private')->header('Pragma', 'no-cache');
    }

    #[Endpoint(title: 'Cabut API key', description: 'Pencabutan permanen dan langsung berlaku pada request berikutnya.')]
    public function revokeKey(Request $request, Integration $integration, string $key): JsonResponse
    {
        $this->authorizePermission('manage_integrations');
        DB::transaction(function () use ($integration, $key, $request) {
            $record = $integration->keys()->lockForUpdate()->findOrFail($key);
            if (! $record->revoked_at) {
                $record->update(['revoked_at' => now()]);
                activity('integrations')->causedBy($request->user())->performedOn($record)->log('integration_key_revoked');
            }
        });

        return $this->success(null, 'API key dicabut.');
    }
}
