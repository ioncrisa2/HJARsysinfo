<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Activitylog\Models\Activity;

class ActivityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('super_admin');
    }

    public function view(User $user, Activity $activity): bool
    {
        return $user->hasRole('super_admin');
    }

    // Make it read-only even for super_admin (recommended)
    public function create(User $user): bool { return false; }
    public function update(User $user, Activity $activity): bool { return false; }
    public function delete(User $user, Activity $activity): bool { return false; }
    public function restore(User $user, Activity $activity): bool { return false; }
    public function forceDelete(User $user, Activity $activity): bool { return false; }
}
