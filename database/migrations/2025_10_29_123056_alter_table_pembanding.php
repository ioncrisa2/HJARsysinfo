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
        Schema::table('data_pembanding',function(Blueprint $table){
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         Schema::table('data_pembanding', function (Blueprint $table) {
            // Menghapus kolom deleted_at jika migration di-rollback
            $table->dropSoftDeletes();
            $table->dropForeign(['created_by']);
            $table->dropColumn('created_by');
        });
    }
};
