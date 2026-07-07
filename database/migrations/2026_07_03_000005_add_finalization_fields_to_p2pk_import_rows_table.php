<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('p2pk_import_rows', function (Blueprint $table): void {
            $table->char('imported_source_fingerprint', 64)->nullable()->after('source_fingerprint');
            $table->string('failure_code', 50)->nullable()->after('last_error');
            $table->foreignId('conflicting_pembanding_id')
                ->nullable()
                ->after('pembanding_id')
                ->constrained('data_pembanding')
                ->nullOnDelete();

            $table->unique('imported_source_fingerprint', 'p2pk_import_source_unique');
        });
    }

    public function down(): void
    {
        Schema::table('p2pk_import_rows', function (Blueprint $table): void {
            $table->dropUnique('p2pk_import_source_unique');
            $table->dropConstrainedForeignId('conflicting_pembanding_id');
            $table->dropColumn(['imported_source_fingerprint', 'failure_code']);
        });
    }
};
