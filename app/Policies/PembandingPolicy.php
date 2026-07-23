<?php

namespace App\Policies;

use App\Models\Pembanding;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PembandingPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view map distribution data.
     */
    public function viewMap(User $user): bool
    {
        return $user->can('view_map');
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_data::pembanding');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Pembanding $pembanding): bool
    {
        return $user->can('view_data::pembanding')
            || $user->can('view_any_data::pembanding');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_data::pembanding');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Pembanding $pembanding): bool
    {
        return $user->can('update_data::pembanding')
            || (
                $user->can('update_own_data::pembanding')
                && (int) $pembanding->created_by === (int) $user->id
            );
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Pembanding $pembanding): bool
    {
        return $user->can('delete_data::pembanding');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_data::pembanding');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, Pembanding $pembanding): bool
    {
        return $user->can('force_delete_data::pembanding');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_data::pembanding');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, Pembanding $pembanding): bool
    {
        return $user->can('restore_data::pembanding');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_data::pembanding');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, Pembanding $pembanding): bool
    {
        return $user->can('replicate_data::pembanding');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_data::pembanding');
    }
}
