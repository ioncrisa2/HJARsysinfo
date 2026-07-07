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

    $this->get('/register-data-contributor/plain-token')
        ->assertOk()
        ->assertViewHas('page', fn (array $page): bool => $page['component'] === 'Auth/DataContributorRegister');

    $this->post('/register-data-contributor/plain-token', [
        'display_name' => 'Budi Santoso',
        'phone' => '0812-3456-7890',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertRedirect('/register-data-contributor/submitted');

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

    $this->get('/register-data-contributor/plain-token')
        ->assertOk()
        ->assertViewHas('page', fn (array $page): bool => $page['component'] === 'Auth/DataContributorRegisterInvalid');
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

    $this->actingAs($superAdmin)
        ->post("/app/data-contributor-registration-requests/{$registrationRequest->id}/accept")
        ->assertRedirect('/app/data-contributor-invitations');

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

it('blocks invitation administration for non super admin users even when permission is assigned', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('manage_data_contributor_invitations', 'web');
    $user->givePermissionTo('manage_data_contributor_invitations');

    $this->actingAs($user)
        ->post('/app/data-contributor-invitations')
        ->assertForbidden();
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

    $this->actingAs($superAdmin)
        ->delete("/app/data-contributor-invitations/{$unusedInvite->id}")
        ->assertRedirect('/app/data-contributor-invitations?tab=tokens');

    $this->assertDatabaseMissing('data_contributor_invites', ['id' => $unusedInvite->id]);

    $this->actingAs($superAdmin)
        ->delete("/app/data-contributor-invitations/{$submittedInvite->id}")
        ->assertSessionHas('error', 'Invitation hanya bisa dihapus jika belum digunakan.');

    $this->assertDatabaseHas('data_contributor_invites', ['id' => $submittedInvite->id]);
});
