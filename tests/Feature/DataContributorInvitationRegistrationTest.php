<?php

use App\Models\DataContributorInvite;
use App\Models\DataContributorRegistrationRequest;
use App\Models\User;
use Database\Seeders\PembandingAccessRoleSeeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->seed(PembandingAccessRoleSeeder::class);
});

function createContributorInvite(User $creator, string $token = 'valid-token'): DataContributorInvite
{
    return DataContributorInvite::query()->create([
        'token_hash' => DataContributorInvite::hashToken($token),
        'created_by' => $creator->id,
        'expires_at' => now()->addDays(7),
        'status' => DataContributorInvite::STATUS_UNUSED,
    ]);
}

it('stores public registration request from a valid one-time invitation token', function () {
    $creator = User::factory()->create();
    $invite = createContributorInvite($creator, 'plain-token');

    $this->getJson('/api/v1/public/data-contributor-registration/plain-token')
        ->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.valid', true);

    $this->postJson('/api/v1/public/data-contributor-registration/plain-token', [
        'display_name' => 'Budi Santoso',
        'phone' => '0812-3456-7890',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertCreated()
        ->assertJsonPath('status', 'success');

    $registrationRequest = DataContributorRegistrationRequest::query()->firstOrFail();

    expect($registrationRequest->invite_id)->toBe($invite->id)
        ->and($registrationRequest->display_name)->toBe('Budi Santoso')
        ->and($registrationRequest->generated_email)->toBe('budi.santoso@kjpp-hjar.co.id')
        ->and($registrationRequest->phone)->toBe('081234567890')
        ->and($registrationRequest->status)->toBe(DataContributorRegistrationRequest::STATUS_PENDING)
        ->and($registrationRequest->password_hash)->not->toBe('password123')
        ->and(Hash::check('password123', $registrationRequest->password_hash))->toBeTrue();

    $invite->refresh();

    expect($invite->status)->toBe(DataContributorInvite::STATUS_SUBMITTED)
        ->and($invite->used_at)->not->toBeNull();

    $this->getJson('/api/v1/public/data-contributor-registration/plain-token')
        ->assertOk()
        ->assertJsonPath('data.valid', false);
});

it('accepts a pending request and creates a data contributor user with the stored password hash', function () {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super_admin');
    $invite = createContributorInvite($superAdmin, 'accept-token');

    $registrationRequest = DataContributorRegistrationRequest::query()->create([
        'invite_id' => $invite->id,
        'display_name' => 'Sari Data',
        'generated_email' => 'sari.data@kjpp-hjar.co.id',
        'phone' => '081111111111',
        'password_hash' => Hash::make('secret123'),
        'status' => DataContributorRegistrationRequest::STATUS_PENDING,
        'submitted_at' => now(),
    ]);

    $this->actingAs($superAdmin, 'sanctum')
        ->postJson("/api/v1/data-contributor-registration-requests/{$registrationRequest->id}/accept")
        ->assertOk()
        ->assertJsonPath('status', 'success');

    $user = User::query()->where('email', 'sari.data@kjpp-hjar.co.id')->firstOrFail();

    expect($user->name)->toBe('Sari Data')
        ->and($user->hasRole('data_contributor'))->toBeTrue()
        ->and(Hash::check('secret123', $user->getAuthPassword()))->toBeTrue();

    $registrationRequest->refresh();
    $invite->refresh();

    expect($registrationRequest->status)->toBe(DataContributorRegistrationRequest::STATUS_ACCEPTED)
        ->and($registrationRequest->accepted_by)->toBe($superAdmin->id)
        ->and($registrationRequest->accepted_at)->not->toBeNull()
        ->and($invite->status)->toBe(DataContributorInvite::STATUS_ACCEPTED);
});

it('allows invitation administration based on permission without requiring a specific role', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('manage_data_contributor_invitations', 'web');
    $user->givePermissionTo('manage_data_contributor_invitations');

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/data-contributor-invitations')
        ->assertCreated()
        ->assertJsonPath('status', 'success');

    $this->assertDatabaseHas('data_contributor_invites', [
        'created_by' => $user->id,
        'status' => DataContributorInvite::STATUS_UNUSED,
    ]);
});

it('allows super admin to delete only unused invitations', function () {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super_admin');

    $unusedInvite = createContributorInvite($superAdmin, 'unused-delete-token');
    $submittedInvite = createContributorInvite($superAdmin, 'submitted-delete-token');
    $submittedInvite->forceFill([
        'status' => DataContributorInvite::STATUS_SUBMITTED,
        'used_at' => now(),
    ])->save();

    DataContributorRegistrationRequest::query()->create([
        'invite_id' => $submittedInvite->id,
        'display_name' => 'Submitted User',
        'generated_email' => 'submitted.user@kjpp-hjar.co.id',
        'phone' => '081222222222',
        'password_hash' => Hash::make('secret123'),
        'status' => DataContributorRegistrationRequest::STATUS_PENDING,
        'submitted_at' => now(),
    ]);

    $this->actingAs($superAdmin, 'sanctum')
        ->deleteJson("/api/v1/data-contributor-invitations/{$unusedInvite->id}")
        ->assertOk()
        ->assertJsonPath('status', 'success');

    $this->assertDatabaseMissing('data_contributor_invites', ['id' => $unusedInvite->id]);

    $this->actingAs($superAdmin, 'sanctum')
        ->deleteJson("/api/v1/data-contributor-invitations/{$submittedInvite->id}")
        ->assertStatus(422)
        ->assertJsonPath('status', 'error');

    $this->assertDatabaseHas('data_contributor_invites', ['id' => $submittedInvite->id]);
});
