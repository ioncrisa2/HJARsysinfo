<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('export_runs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->index();
            $table->string('format', 20);
            $table->string('mode', 20)->nullable();
            $table->string('profile', 40);
            $table->string('scope', 20);
            $table->json('filters')->nullable();
            $table->json('selected_ids')->nullable();
            $table->json('columns')->nullable();
            $table->timestamp('snapshot_at');
            $table->unsignedInteger('total_records')->default(0);
            $table->unsignedInteger('processed_records')->default(0);
            $table->string('disk', 40)->default('local');
            $table->string('path')->nullable();
            $table->string('filename')->nullable();
            $table->string('checksum', 64)->nullable();
            $table->text('error')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('downloaded_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('export_runs');
    }
};
