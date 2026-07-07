<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('p2pk_import_rows', function (Blueprint $table): void {
            $table->string('staging_image_disk', 30)->nullable()->after('warnings');
            $table->string('staging_image_path')->nullable()->after('staging_image_disk');
            $table->string('staging_image_original_name')->nullable()->after('staging_image_path');
            $table->string('staging_image_mime', 100)->nullable()->after('staging_image_original_name');
            $table->unsignedBigInteger('staging_image_size')->nullable()->after('staging_image_mime');
        });
    }

    public function down(): void
    {
        Schema::table('p2pk_import_rows', function (Blueprint $table): void {
            $table->dropColumn([
                'staging_image_disk',
                'staging_image_path',
                'staging_image_original_name',
                'staging_image_mime',
                'staging_image_size',
            ]);
        });
    }
};
