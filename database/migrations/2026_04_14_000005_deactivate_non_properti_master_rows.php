<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // "Non properti" context is deprecated. Mark it inactive in master tables.
        // This avoids data loss while removing it from UI options and dashboard breakdowns.

        if (Schema::hasTable('master_jenis_objek')) {
            DB::table('master_jenis_objek')
                ->where(function ($q) {
                    $q->whereIn('slug', ['non-properti', 'non_properti', 'nonproperti', 'non_property', 'non-properties', 'non_properties'])
                        ->orWhereRaw('LOWER(name) LIKE ?', ['%non properti%']);
                })
                ->update(['is_active' => 0]);
        }

        if (Schema::hasTable('master_jenis_listing')) {
            DB::table('master_jenis_listing')
                ->where(function ($q) {
                    $q->whereIn('slug', ['non-properti', 'non_properti', 'nonproperti', 'non_property', 'non-properties', 'non_properties'])
                        ->orWhereRaw('LOWER(name) LIKE ?', ['%non properti%']);
                })
                ->update(['is_active' => 0]);
        }
    }

    public function down(): void
    {
        // No-op: we don't know which rows were intentionally inactive.
    }
};
