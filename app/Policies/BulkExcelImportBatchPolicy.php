<?php

namespace App\Policies;

use App\Models\BulkExcelImportBatch;
use App\Models\User;

class BulkExcelImportBatchPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('bulk_import_data::pembanding');
    }

    public function view(User $user, BulkExcelImportBatch $batch): bool
    {
        return $user->hasRole('super_admin') || $batch->owner_id === $user->id;
    }

    public function update(User $user, BulkExcelImportBatch $batch): bool
    {
        return $this->view($user, $batch);
    }
}
