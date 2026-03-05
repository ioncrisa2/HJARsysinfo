<?php

namespace App\Policies;

use App\Models\PembandingDeleteRequest;
use App\Models\User;

class PembandingDeleteRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('super_admin');
    }

    public function view(User $user, PembandingDeleteRequest $pembandingDeleteRequest): bool
    {
        return $user->hasRole('super_admin');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, PembandingDeleteRequest $pembandingDeleteRequest): bool
    {
        return $user->hasRole('super_admin');
    }

    public function delete(User $user, PembandingDeleteRequest $pembandingDeleteRequest): bool
    {
        return false;
    }

    public function restore(User $user, PembandingDeleteRequest $pembandingDeleteRequest): bool
    {
        return false;
    }

    public function forceDelete(User $user, PembandingDeleteRequest $pembandingDeleteRequest): bool
    {
        return false;
    }
}
