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
        Schema::table('np_comparables', function (Blueprint $table) {
            $table->foreignId('deleted_by_id')
                ->nullable()
                ->after('updated_by')
                ->constrained('users')
                ->nullOnDelete();
            $table->text('deleted_reason')
                ->nullable()
                ->after('deleted_by_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('np_comparables', function (Blueprint $table) {
            $table->dropConstrainedForeignId('deleted_by_id');
            $table->dropColumn('deleted_reason');
        });
    }
};
