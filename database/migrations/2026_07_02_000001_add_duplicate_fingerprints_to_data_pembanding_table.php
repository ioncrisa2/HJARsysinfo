<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('data_pembanding', function (Blueprint $table): void {
            $table->char('image_checksum', 64)->nullable()->after('image');
            $table->char('business_fingerprint', 64)->nullable()->after('image_checksum');
            $table->char('active_fingerprint', 64)->nullable()->after('business_fingerprint');

            $table->index('business_fingerprint', 'dp_business_fingerprint_idx');
        });
    }

    public function down(): void
    {
        Schema::table('data_pembanding', function (Blueprint $table): void {
            $table->dropIndex('dp_business_fingerprint_idx');
            $table->dropColumn([
                'image_checksum',
                'business_fingerprint',
                'active_fingerprint',
            ]);
        });
    }
};
