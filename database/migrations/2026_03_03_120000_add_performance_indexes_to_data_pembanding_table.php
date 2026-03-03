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
        Schema::table('data_pembanding', function (Blueprint $table) {
            $table->index(
                ['district_id', 'deleted_at', 'tanggal_data'],
                'dp_district_deleted_tanggal_idx'
            );

            $table->index(
                ['regency_id', 'deleted_at'],
                'dp_regency_deleted_idx'
            );

            $table->index(
                ['deleted_at', 'tanggal_data'],
                'dp_deleted_tanggal_idx'
            );

            $table->index(
                ['deleted_at', 'harga'],
                'dp_deleted_harga_idx'
            );

            $table->index(
                ['latitude', 'longitude'],
                'dp_lat_lng_idx'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('data_pembanding', function (Blueprint $table) {
            $table->dropIndex('dp_district_deleted_tanggal_idx');
            $table->dropIndex('dp_regency_deleted_idx');
            $table->dropIndex('dp_deleted_tanggal_idx');
            $table->dropIndex('dp_deleted_harga_idx');
            $table->dropIndex('dp_lat_lng_idx');
        });
    }
};
