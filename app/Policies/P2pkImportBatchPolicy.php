<?php

namespace App\Policies;

use App\Models\P2pkImportBatch;
use App\Models\User;

class P2pkImportBatchPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('bulk_import_data::pembanding');
    }

    public function view(User $user, P2pkImportBatch $batch): bool
    {
        return $user->hasRole('super_admin') || $batch->owner_id === $user->id;
    }

    public function update(User $user, P2pkImportBatch $batch): bool
    {
        return $this->view($user, $batch);
    }
}
