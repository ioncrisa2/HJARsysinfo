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
        Schema::create('data_pembanding', function (Blueprint $table) {
            $table->id();
            
            $table->string('nama_pemberi_informasi');
            $table->string('nomer_telepon_pemberi_informasi');
            $table->text('alamat_data');
            
            $table->string('province_id', 2)->nullable();
            $table->string('regency_id', 4)->nullable();
            $table->string('district_id', 7)->nullable();
            $table->string('village_id', 10)->nullable();
            $table->decimal('latitude', 10, 6);
            $table->decimal('longitude', 10, 6);
            
            $table->foreignId('jenis_listing_id')->nullable()->constrained('master_jenis_listing')->nullOnDelete();
            $table->foreignId('jenis_objek_id')->nullable()->constrained('master_jenis_objek')->nullOnDelete();
            $table->foreignId('status_pemberi_informasi_id')->nullable()->constrained('master_status_pemberi_informasi')->nullOnDelete();
            $table->foreignId('bentuk_tanah_id')->nullable()->constrained('master_bentuk_tanah')->nullOnDelete();
            $table->foreignId('dokumen_tanah_id')->nullable()->constrained('master_dokumen_tanah')->nullOnDelete();
            $table->foreignId('posisi_tanah_id')->nullable()->constrained('master_posisi_tanah')->nullOnDelete();
            $table->foreignId('kondisi_tanah_id')->nullable()->constrained('master_kondisi_tanah')->nullOnDelete();
            $table->foreignId('topografi_id')->nullable()->constrained('master_topografi')->nullOnDelete();
            $table->foreignId('peruntukan_id')->nullable()->constrained('master_peruntukan')->nullOnDelete();

            $table->decimal('luas_tanah', 12, 2)->nullable();
            $table->decimal('luas_bangunan', 12, 2)->nullable();
            $table->year('tahun_bangun')->nullable();
            $table->decimal('lebar_depan', 8, 2)->nullable();
            $table->decimal('lebar_jalan', 8, 2)->nullable();
            $table->string('rasio_tapak', 50)->nullable();
            
            $table->bigInteger('harga')->nullable();
            $table->decimal('jangka_waktu_sewa', 8, 2)->nullable();
            $table->string('satuan_waktu_sewa', 50)->nullable();
            
            $table->date('tanggal_data')->nullable();
            $table->text('image')->nullable();
            $table->text('catatan')->nullable();
            
            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            
            $table->timestamps();
            
            $table->softDeletes();
            $table->foreignId('deleted_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('deleted_reason')->nullable();
            
            $table->index(['district_id', 'deleted_at', 'tanggal_data'], 'dp_district_deleted_tanggal_idx');
            $table->index(['regency_id', 'deleted_at'], 'dp_regency_deleted_idx');
            $table->index(['deleted_at', 'tanggal_data'], 'dp_deleted_tanggal_idx');
            $table->index(['deleted_at', 'harga'], 'dp_deleted_harga_idx');
            $table->index(['latitude', 'longitude'], 'dp_lat_lng_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_pembanding');
    }
};
