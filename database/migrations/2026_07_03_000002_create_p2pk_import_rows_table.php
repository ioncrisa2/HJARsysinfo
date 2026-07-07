<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('p2pk_import_rows', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('batch_id')->constrained('p2pk_import_batches')->cascadeOnDelete();
            $table->unsignedInteger('source_row_number');
            $table->char('source_fingerprint', 64);
            $table->string('status', 30)->default('incomplete');
            $table->boolean('is_selected')->default(true);
            $table->json('raw_payload');
            $table->json('mapped_payload');
            $table->json('missing_fields')->nullable();
            $table->json('warnings')->nullable();
            $table->foreignId('duplicate_of_row_id')->nullable()->constrained('p2pk_import_rows')->nullOnDelete();
            $table->foreignId('pembanding_id')->nullable()->constrained('data_pembanding')->nullOnDelete();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->unique(['batch_id', 'source_row_number'], 'p2pk_row_batch_number_unique');
            $table->index(['batch_id', 'status', 'is_selected'], 'p2pk_row_batch_status_idx');
            $table->index(['source_fingerprint', 'pembanding_id'], 'p2pk_row_source_result_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('p2pk_import_rows');
    }
};
