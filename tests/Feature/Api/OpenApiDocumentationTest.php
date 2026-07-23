<?php

use App\Models\User;
use Illuminate\Support\Facades\Gate;

it('publishes a complete OpenAPI document for the API', function () {
    Gate::define('viewApiDocs', fn (?User $user): bool => true);

    $response = $this->getJson('/docs/api.json')
        ->assertOk()
        ->assertJsonPath('openapi', '3.1.0')
        ->assertJsonPath('info.title', 'Comparable Data API')
        ->assertJsonPath('components.securitySchemes.http.type', 'http')
        ->assertJsonPath('components.securitySchemes.http.scheme', 'bearer')
        ->assertJsonPath('paths./auth/login.post.security', [])
        ->assertJsonPath('paths./v1/pembandings.get.summary', 'Lihat daftar pembanding');

    $document = $response->json();
    $dictionaryParameters = collect($document['paths']['/v1/dictionaries/{type}']['get']['parameters'])
        ->keyBy('name');

    expect($document['paths'])->toHaveCount(18)
        ->and(collect($document['paths'])->sum(fn (array $operations): int => count($operations)))->toBe(23)
        ->and(data_get(
            $document,
            'paths./v1/pembandings.get.responses.200.content.application/json.schema.properties.data.properties.data.items.$ref'
        ))->toBe('#/components/schemas/PembandingResource')
        ->and(collect(data_get($document, 'paths./v1/locations/provinces.get.parameters'))
            ->pluck('name')
            ->all())->toBe(['q', 'limit'])
        ->and($dictionaryParameters['type']['schema']['enum'])->toContain('jenis-objek', 'peruntukan');
});
