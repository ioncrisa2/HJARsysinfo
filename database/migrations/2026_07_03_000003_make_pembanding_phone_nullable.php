<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('data_pembanding', function (Blueprint $table): void {
            $table->string('nomer_telepon_pemberi_informasi')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('data_pembanding')
            ->whereNull('nomer_telepon_pemberi_informasi')
            ->update(['nomer_telepon_pemberi_informasi' => '']);

        Schema::table('data_pembanding', function (Blueprint $table): void {
            $table->string('nomer_telepon_pemberi_informasi')->nullable(false)->change();
        });
    }
};
