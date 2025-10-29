<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Teguh02\IndonesiaTerritoryForms\IndonesiaTerritoryForms;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('data_pembanding', function (Blueprint $table) {
            $table->id();
            $table->string('nama_pemberi_informasi')->required();
            $table->string('nomer_telepon_pemberi_informasi')->required();
            $table->string('status_pemberi_informasi');
            $table->string('jenis_listing', 50)->nullable()->required();
            $table->string('jenis_objek', 50)->nullable()->required();
            $table->text('alamat_data')->required();
            $table->decimal('latitude', 10, 6)->required();
            $table->decimal('longitude', 10, 6)->required();
            $table->decimal('luas_tanah', 12, 2)->nullable();
            $table->decimal('luas_bangunan', 12, 2)->nullable();
            $table->year('tahun_bangun')->nullable();
            $table->string('bentuk_tanah', 50)->nullable();
            $table->string('dokumen_tanah', 50)->nullable();
            $table->string('posisi_tanah', 50)->nullable();
            $table->string('kondisi_tanah', 50)->nullable();
            $table->string('topografi', 100)->nullable();
            $table->decimal('lebar_depan', 8, 2)->nullable();
            $table->decimal('lebar_jalan', 8, 2)->nullable();
            $table->string('peruntukan', 100)->nullable();
            $table->string('rasio_tapak', 50)->nullable();
            $table->bigInteger('harga')->nullable();
            $table->date('tanggal_data')->nullable();
            $table->text('image')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index(['jenis_listing','jenis_objek','harga','tanggal_data']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('data_pembanding', function (Blueprint $table) {
            $table->dropIndex([
                'jenis_listing','jenis_objek','harga','tanggal_data',
                'province_id', 'regency_id', 'district_id', 'village_id'
            ]);
            $table->dropIfExists();
        });
    }
};
