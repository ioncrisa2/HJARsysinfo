<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('p2pk_import_batches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('original_filename');
            $table->string('source_disk', 30)->default('local');
            $table->string('source_path');
            $table->char('file_checksum', 64);
            $table->string('sheet_name')->default('Data_Pembanding');
            $table->string('status', 30)->default('draft');
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('selected_rows')->default(0);
            $table->unsignedInteger('ready_rows')->default(0);
            $table->unsignedInteger('imported_rows')->default(0);
            $table->unsignedInteger('failed_rows')->default(0);
            $table->timestamp('last_activity_at')->nullable();
            $table->date('finalization_date')->nullable();
            $table->foreignId('initiated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamps();

            $table->unique(['owner_id', 'file_checksum'], 'p2pk_batch_owner_checksum_unique');
            $table->index(['owner_id', 'status', 'updated_at'], 'p2pk_batch_owner_status_idx');
            $table->index(['status', 'last_activity_at'], 'p2pk_batch_cleanup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('p2pk_import_batches');
    }
};
