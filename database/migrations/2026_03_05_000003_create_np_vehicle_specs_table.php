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
        Schema::create('np_vehicle_specs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('np_comparable_id')
                ->unique()
                ->constrained('np_comparables')
                ->cascadeOnDelete();
            $table->string('vehicle_type', 100);
            $table->string('axle_configuration', 20)->nullable();
            $table->unsignedBigInteger('odometer_km')->nullable();
            $table->string('transmission', 50)->nullable();
            $table->string('fuel_type', 50)->nullable();
            $table->unsignedInteger('engine_cc')->nullable();
            $table->unsignedInteger('payload_kg')->nullable();
            $table->string('body_type', 100)->nullable();
            $table->string('drive_type', 20)->nullable();
            $table->timestamps();

            $table->index(['vehicle_type', 'axle_configuration'], 'np_vehicle_type_axle_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('np_vehicle_specs');
    }
};
