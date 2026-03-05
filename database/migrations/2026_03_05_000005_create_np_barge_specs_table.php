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
        Schema::create('np_barge_specs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('np_comparable_id')
                ->unique()
                ->constrained('np_comparables')
                ->cascadeOnDelete();
            $table->string('barge_type', 100);
            $table->unsignedBigInteger('capacity_dwt')->nullable();
            $table->decimal('loa_m', 10, 2)->nullable();
            $table->decimal('beam_m', 10, 2)->nullable();
            $table->decimal('draft_m', 10, 2)->nullable();
            $table->unsignedInteger('gross_tonnage')->nullable();
            $table->unsignedSmallInteger('built_year')->nullable();
            $table->string('shipyard', 255)->nullable();
            $table->string('hull_material', 100)->nullable();
            $table->string('class_status', 100)->nullable();
            $table->date('certificate_valid_until')->nullable();
            $table->date('last_docking_date')->nullable();
            $table->timestamps();

            $table->index(['barge_type', 'capacity_dwt'], 'np_barge_type_capacity_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('np_barge_specs');
    }
};
