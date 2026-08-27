<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('p2pk_import_batches') && ! Schema::hasTable('bulk_excel_import_batches')) {
            Schema::rename('p2pk_import_batches', 'bulk_excel_import_batches');
        }

        if (Schema::hasTable('p2pk_import_rows') && ! Schema::hasTable('bulk_excel_import_rows')) {
            Schema::rename('p2pk_import_rows', 'bulk_excel_import_rows');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('bulk_excel_import_rows') && ! Schema::hasTable('p2pk_import_rows')) {
            Schema::rename('bulk_excel_import_rows', 'p2pk_import_rows');
        }

        if (Schema::hasTable('bulk_excel_import_batches') && ! Schema::hasTable('p2pk_import_batches')) {
            Schema::rename('bulk_excel_import_batches', 'p2pk_import_batches');
        }
    }
};
