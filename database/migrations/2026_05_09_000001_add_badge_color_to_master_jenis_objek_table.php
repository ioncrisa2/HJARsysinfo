<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_jenis_objek', function (Blueprint $table) {
            $table->string('badge_color', 20)->nullable()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('master_jenis_objek', function (Blueprint $table) {
            $table->dropColumn('badge_color');
        });
    }
};
