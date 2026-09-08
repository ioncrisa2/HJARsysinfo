<?php

use App\Models\User;
use Illuminate\Support\Facades\Gate;

it('publishes a complete OpenAPI document for the API', function () {
    config(['docs.pin' => '']);
    Gate::define('viewApiDocs', fn (?User $user): bool => true);

    $response = $this->getJson('/docs/api.json')
        ->assertOk()
        ->assertJsonPath('openapi', '3.1.0')
        ->assertJsonPath('info.title', 'Comparable Data API')
        ->assertJsonPath('components.securitySchemes.http.type', 'http')
        ->assertJsonPath('components.securitySchemes.http.scheme', 'bearer')
        ->assertJsonPath('components.securitySchemes.integrationKey.scheme', 'bearer')
        ->assertJsonPath('components.securitySchemes.sessionCookie.type', 'apiKey')
        ->assertJsonPath('components.securitySchemes.sessionCookie.in', 'cookie')
        ->assertJsonPath('paths./auth/login.post.security', [])
        ->assertJsonPath('paths./v1/auth/session.post.security', [])
        ->assertJsonPath('paths./v1/auth/session.delete.security.0.sessionCookie', [])
        ->assertJsonPath('paths./v1/auth/me.get.security.0.sessionCookie', [])
        ->assertJsonPath('paths./v1/auth/me.get.security.1.http', [])
        ->assertJsonPath('paths./v1/pembandings.get.summary', 'Lihat daftar pembanding');

    $response
        ->assertJsonPath('paths./v1/pembandings.get.security.2.integrationKey', [])
        ->assertJsonPath('paths./v1/pembandings/similar.post.security.2.integrationKey', [])
        ->assertJsonPath('paths./v1/locations/districts.get.security.2.integrationKey', [])
        ->assertJsonPath('paths./v1/dictionaries/{type}.get.security.2.integrationKey', [])
        ->assertJsonMissingPath('paths./v1/integrations.get.security.2.integrationKey')
        ->assertJsonPath('paths./v1/auth/session.post.tags.0', 'Autentikasi Web')
        ->assertJsonPath('paths./auth/login.post.tags.0', 'Autentikasi Mobile')
        ->assertJsonPath('paths./v1/auth/me.get.tags.0', 'API Bersama - Profil')
        ->assertJsonPath('paths./v1/pembandings.get.security.0.sessionCookie', [])
        ->assertJsonPath('paths./v1/pembandings.get.security.1.http', []);

    foreach (['me' => 'get', 'profile' => 'put', 'profile/password' => 'put'] as $suffix => $method) {
        $response->assertJsonPath("paths./auth/{$suffix}.{$method}.deprecated", true)
            ->assertJsonMissingPath("paths./v1/auth/{$suffix}.{$method}.deprecated");
        expect($response->json("paths./auth/{$suffix}.{$method}.description"))->toContain("/api/v1/auth/{$suffix}");
    }
    $response->assertJsonPath('paths./v1/pembanding-submissions/{submission}/resolve.post.deprecated', true)
        ->assertJsonMissingPath('paths./v1/pembanding-submissions/{submission}/resolution.post.deprecated')
        ->assertJsonMissingPath('paths./auth/login.post.deprecated')
        ->assertJsonMissingPath('paths./auth/refresh.post.deprecated')
        ->assertJsonMissingPath('paths./auth/logout.post.deprecated');

    $document = $response->json();
    $operationIds = collect($document['paths'])->flatMap(fn (array $operations) => collect($operations)->pluck('operationId'));
    expect($operationIds->unique()->count())->toBe($operationIds->count());
    $dictionaryParameters = collect($document['paths']['/v1/dictionaries/{type}']['get']['parameters'])
        ->keyBy('name');
    $similarSchema = data_get($document, 'components.schemas.SimilarPembandingResource');
    $similarProperties = $similarSchema['properties'];

    expect(count($document['paths']))->toBeGreaterThanOrEqual(18)
        ->and(collect($document['paths'])->sum(fn (array $operations): int => count($operations)))->toBeGreaterThanOrEqual(23)
        ->and(
            data_get($document, 'paths./v1/pembandings.get.responses.200.content.application/json.schema.properties.data.items.$ref')
            ?? data_get($document, 'paths./v1/pembandings.get.responses.200.content.application/json.schema.properties.data.properties.data.items.$ref')
        )->toBe('#/components/schemas/PembandingResource')
        ->and(collect(data_get($document, 'paths./v1/locations/provinces.get.parameters'))
            ->pluck('name')
            ->all())->toBe(['q', 'limit'])
        ->and($dictionaryParameters['type']['schema']['enum'])->toContain('jenis-objek', 'peruntukan')
        ->and(data_get(
            $document,
            'paths./v1/pembandings/similar.post.requestBody.content.application/json.schema.$ref'
        ))->toBe('#/components/schemas/FindSimilarPembandingRequest')
        ->and(data_get(
            $document,
            'paths./v1/pembandings/similar.post.responses.200.content.application/json.schema.anyOf.0.properties.data.items.$ref'
        ))->toBe('#/components/schemas/SimilarPembandingResource')
        ->and(data_get(
            $document,
            'paths./v1/pembandings/{id}/similar.get.responses.200.content.application/json.schema.anyOf.0.properties.data.items.$ref'
        ))->toBe('#/components/schemas/SimilarPembandingResource')
        ->and($similarProperties['rankable']['type'])->toBe(['boolean', 'null'])
        ->and($similarProperties['eligibility_reasons']['type'])->toBe('array')
        ->and($similarProperties['evidence_quality']['type'])->toBe(['object', 'null'])
        ->and($similarProperties['component_scores']['type'])->toBe('object')
        ->and($similarProperties['warnings']['items']['type'])->toBe('string')
        ->and($similarProperties['report_missing_fields']['items']['type'])->toBe('string')
        ->and($similarSchema['required'])->toContain(
            'similarity_score',
            'rankable',
            'component_scores',
            'report_missing_fields',
        );
});
