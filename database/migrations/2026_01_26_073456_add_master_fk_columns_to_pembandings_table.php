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
            // Listing / object / info source
            $table->foreignId('jenis_listing_id')->nullable()
                ->after('jenis_listing')
                ->constrained('master_jenis_listing')->nullOnDelete();

            $table->foreignId('jenis_objek_id')->nullable()
                ->after('jenis_objek')
                ->constrained('master_jenis_objek')->nullOnDelete();

            $table->foreignId('status_pemberi_informasi_id')->nullable()
                ->after('status_pemberi_informasi')
                ->constrained('master_status_pemberi_informasi')->nullOnDelete();

            // Land-related master data
            $table->foreignId('bentuk_tanah_id')->nullable()
                ->after('bentuk_tanah')
                ->constrained('master_bentuk_tanah')->nullOnDelete();

            $table->foreignId('dokumen_tanah_id')->nullable()
                ->after('dokumen_tanah')
                ->constrained('master_dokumen_tanah')->nullOnDelete();

            $table->foreignId('posisi_tanah_id')->nullable()
                ->after('posisi_tanah')
                ->constrained('master_posisi_tanah')->nullOnDelete();

            $table->foreignId('kondisi_tanah_id')->nullable()
                ->after('kondisi_tanah')
                ->constrained('master_kondisi_tanah')->nullOnDelete();

            $table->foreignId('topografi_id')->nullable()
                ->after('topografi')
                ->constrained('master_topografi')->nullOnDelete();

            $table->foreignId('peruntukan_id')->nullable()
                ->after('peruntukan')
                ->constrained('master_peruntukan')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('data_pembanding', function (Blueprint $table) {
            $table->dropConstrainedForeignId('jenis_listing_id');
            $table->dropConstrainedForeignId('jenis_objek_id');
            $table->dropConstrainedForeignId('status_pemberi_informasi_id');
            $table->dropConstrainedForeignId('bentuk_tanah_id');
            $table->dropConstrainedForeignId('dokumen_tanah_id');
            $table->dropConstrainedForeignId('posisi_tanah_id');
            $table->dropConstrainedForeignId('kondisi_tanah_id');
            $table->dropConstrainedForeignId('topografi_id');
            $table->dropConstrainedForeignId('peruntukan_id');
        });
    }
};
