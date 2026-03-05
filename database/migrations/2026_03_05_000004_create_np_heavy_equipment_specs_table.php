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
        Schema::create('np_heavy_equipment_specs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('np_comparable_id')
                ->unique()
                ->constrained('np_comparables')
                ->cascadeOnDelete();
            $table->string('equipment_type', 100);
            $table->unsignedBigInteger('hour_meter')->nullable();
            $table->unsignedInteger('operating_weight_kg')->nullable();
            $table->decimal('bucket_capacity_m3', 8, 3)->nullable();
            $table->unsignedInteger('engine_power_hp')->nullable();
            $table->string('undercarriage_type', 50)->nullable();
            $table->string('undercarriage_condition', 50)->nullable();
            $table->string('attachment', 100)->nullable();
            $table->text('service_history_note')->nullable();
            $table->timestamps();

            $table->index(['equipment_type', 'hour_meter'], 'np_heavy_equipment_type_hour_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('np_heavy_equipment_specs');
    }
};
