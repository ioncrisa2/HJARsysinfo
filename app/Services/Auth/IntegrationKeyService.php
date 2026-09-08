<?php

namespace App\Services\Auth;

use App\Models\Integration;
use App\Models\User;
use App\Support\IntegrationAccess;
use Illuminate\Support\Facades\DB;

class IntegrationKeyService
{
    public function issue(Integration $integration, User $actor, array $data): array
    {
        return DB::transaction(function () use ($integration, $actor, $data) {
            $integration = Integration::query()->lockForUpdate()->findOrFail($integration->id);
            abort_unless($integration->is_active, 409, 'Aktifkan integrasi sebelum menerbitkan key.');
            $plainText = IntegrationAccess::PREFIX.bin2hex(random_bytes(32));
            $key = $integration->keys()->create([
                'name' => $data['name'],
                'prefix' => substr($plainText, 0, 20),
                'key_hash' => hash('sha256', $plainText),
                'scopes' => array_values(array_unique($data['scopes'])),
                'expires_at' => $data['expires_at'],
                'created_by' => $actor->id,
            ]);
            activity('integrations')->causedBy($actor)->performedOn($key)
                ->withProperties(['integration_id' => $integration->id, 'scopes' => $key->scopes])
                ->log('integration_key_issued');

            return ['key' => $key, 'plain_text_key' => $plainText];
        });
    }
}
