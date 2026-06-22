<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('data_pembanding')) {
            return;
        }

        if (! Schema::hasColumn('data_pembanding', 'jangka_waktu_sewa')) {
            Schema::table('data_pembanding', function (Blueprint $table) {
                $table->decimal('jangka_waktu_sewa', 8, 2)->nullable()->after('harga');
            });
        }

        if (! Schema::hasColumn('data_pembanding', 'satuan_waktu_sewa')) {
            Schema::table('data_pembanding', function (Blueprint $table) {
                $table->string('satuan_waktu_sewa', 50)->nullable()->after('jangka_waktu_sewa');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('data_pembanding')) {
            return;
        }

        if (Schema::hasColumn('data_pembanding', 'satuan_waktu_sewa')) {
            Schema::table('data_pembanding', function (Blueprint $table) {
                $table->dropColumn('satuan_waktu_sewa');
            });
        }

        if (Schema::hasColumn('data_pembanding', 'jangka_waktu_sewa')) {
            Schema::table('data_pembanding', function (Blueprint $table) {
                $table->dropColumn('jangka_waktu_sewa');
            });
        }
    }
};
