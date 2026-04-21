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
            $table->decimal('jangka_waktu_sewa', 8, 2)->nullable()->after('harga');
            $table->string('satuan_waktu_sewa', 50)->nullable()->after('jangka_waktu_sewa');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('data_pembanding', function (Blueprint $table) {
            $table->dropColumn(['jangka_waktu_sewa', 'satuan_waktu_sewa']);
        });
    }
};
