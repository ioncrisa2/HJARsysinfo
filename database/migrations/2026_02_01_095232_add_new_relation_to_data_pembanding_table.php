<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected array $columns = [
        'jenis_listing_id' => 'master_jenis_listing',
        'jenis_objek_id' => 'master_jenis_objek',
        'status_pemberi_informasi_id' => 'master_status_pemberi_informasi',
        'bentuk_tanah_id' => 'master_bentuk_tanah',
        'dokumen_tanah_id' => 'master_dokumen_tanah',
        'posisi_tanah_id' => 'master_posisi_tanah',
        'kondisi_tanah_id' => 'master_kondisi_tanah',
        'topografi_id' => 'master_topografi',
        'peruntukan_id' => 'master_peruntukan',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->columns as $column => $tableName) {
            if (Schema::hasColumn('data_pembanding', $column)) {
                continue;
            }

            Schema::table('data_pembanding', function (Blueprint $table) use ($column, $tableName) {
                $table->foreignId($column)
                    ->nullable()
                    ->constrained($tableName)
                    ->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (array_keys($this->columns) as $column) {
            if (! Schema::hasColumn('data_pembanding', $column)) {
                continue;
            }

            Schema::table('data_pembanding', function (Blueprint $table) use ($column) {
                $table->dropConstrainedForeignId($column);
            });
        }
    }
};
