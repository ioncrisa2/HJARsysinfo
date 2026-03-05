<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pembanding_delete_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pembanding_id')
                ->constrained('data_pembanding')
                ->cascadeOnDelete();
            $table->foreignId('requested_by_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->text('reason');
            $table->string('status', 20)->default('pending')->index();
            $table->text('review_note')->nullable();
            $table->foreignId('reviewed_by_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['pembanding_id', 'status'], 'pdr_pembanding_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pembanding_delete_requests');
    }
};
