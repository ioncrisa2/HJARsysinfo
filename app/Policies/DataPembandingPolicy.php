<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Pembanding;

class DataPembandingPolicy
{
    public function view(User $user)
    {
        return $user->hasPermissionTo('view_data::pembanding');
    }

    public function create(User $user)
    {
        return $user->hasPermissionTo('create_data::pembanding');
    }

    public function update(User $user, Pembanding $pembanding)
    {
        return $user->hasPermissionTo('update_data::pembanding');
    }

    public function delete(User $user, Pembanding $pembanding)
    {
        return $user->hasPermissionTo('delete_data::pembanding');
    }
}
