<?php

use App\Models\User;
use Database\Seeders\PembandingAccessRoleSeeder;

it('limits data contributor dashboard to map and stat card props', function () {
    $this->seed(PembandingAccessRoleSeeder::class);

    $user = User::factory()->create(['deactivated_at' => null]);
    $user->assignRole('data_contributor');

    $this->actingAs($user)
        ->get('/home')
        ->assertOk()
        ->assertSee('data_contributor')
        ->assertSee('mapPoints')
        ->assertSee('stats')
        ->assertDontSee('recentData')
        ->assertDontSee('monthlyData')
        ->assertDontSee('topContributors')
        ->assertDontSee('objectTypeCounts');
});
