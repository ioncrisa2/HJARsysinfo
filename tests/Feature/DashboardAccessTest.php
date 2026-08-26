<?php

use App\Models\User;
use Database\Seeders\PembandingAccessRoleSeeder;

it('limits data contributor dashboard to map and stat card props', function () {
    $this->seed(PembandingAccessRoleSeeder::class);

    $user = User::factory()->create(['deactivated_at' => null]);
    $user->assignRole('data_contributor');

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/dashboard')
        ->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.dashboard_variant', 'data_contributor')
        ->assertJsonStructure([
            'data' => [
                'dashboard_variant',
                'map_points',
                'stats',
                'jenis_listing_options',
                'can',
                'can_widgets',
                'delete_request_alert',
            ],
        ]);

    expect($response->json('data.can.create_data'))->toBeTrue()
        ->and($response->json('data'))->not->toHaveKeys(['recent_data', 'monthly_data', 'top_contributors']);
});
