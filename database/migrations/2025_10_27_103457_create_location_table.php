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
       Schema::create('provinces', function (Blueprint $table) {
            $table->string('id', 2)->primary(); // e.g., '32'
            $table->string('name');
            $table->timestamps();
        });

       Schema::create('regencies', function (Blueprint $table) {
            $table->string('id', 4)->primary(); // e.g., '32.73'
            $table->string('province_id', 2);
            $table->string('name');
            $table->timestamps();

            $table->foreign('province_id')
                  ->references('id')
                  ->on('provinces')
                  ->onDelete('cascade');
        });

        Schema::create('districts', function (Blueprint $table) {
            $table->string('id', 7)->primary(); // e.g., '32.73.01'
            $table->string('regency_id', 4);
            $table->string('name');
            $table->timestamps();

            $table->foreign('regency_id')
                  ->references('id')
                  ->on('regencies')
                  ->onDelete('cascade');
        });

        Schema::create('villages', function (Blueprint $table) {
            $table->string('id', 10)->primary(); // e.g., '32.73.01.1001'
            $table->string('district_id', 7);
            $table->string('name');
            $table->timestamps();

            $table->foreign('district_id')
                  ->references('id')
                  ->on('districts')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('villages');
        Schema::dropIfExists('districts');
        Schema::dropIfExists('regencies');
        Schema::dropIfExists('provinces');
    }
};
