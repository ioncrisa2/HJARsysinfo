<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected array $legacyColumns = [
        'jenis_listing',
        'jenis_objek',
        'status_pemberi_informasi',
        'bentuk_tanah',
        'dokumen_tanah',
        'posisi_tanah',
        'kondisi_tanah',
        'topografi',
        'peruntukan',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $existingColumns = array_values(array_filter(
            $this->legacyColumns,
            fn (string $column) => Schema::hasColumn('data_pembanding', $column)
        ));

        if (empty($existingColumns)) {
            return;
        }

        Schema::table('data_pembanding', function (Blueprint $table) use ($existingColumns) {
            $table->dropColumn($existingColumns);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('data_pembanding', function (Blueprint $table) {
            // Recreate legacy columns if rollback is needed
            $table->string('jenis_listing')->nullable();
            $table->string('jenis_objek')->nullable();
            $table->string('status_pemberi_informasi')->nullable();

            $table->string('bentuk_tanah')->nullable();
            $table->string('dokumen_tanah')->nullable();
            $table->string('posisi_tanah')->nullable();
            $table->string('kondisi_tanah')->nullable();
            $table->string('topografi')->nullable();
            $table->string('peruntukan')->nullable();
        });
    }
};
