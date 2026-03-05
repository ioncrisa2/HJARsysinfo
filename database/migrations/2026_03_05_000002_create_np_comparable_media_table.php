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
        Schema::create('np_comparable_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('np_comparable_id')
                ->constrained('np_comparables')
                ->cascadeOnDelete();
            $table->enum('media_type', ['image', 'document', 'link'])->default('image');
            $table->string('file_path', 500)->nullable();
            $table->text('external_url')->nullable();
            $table->string('caption')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['np_comparable_id', 'media_type', 'sort_order'], 'np_cmp_media_lookup_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('np_comparable_media');
    }
};
