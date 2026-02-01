<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('data_pembanding', function (Blueprint $table) {
            $table->foreignId('deleted_by_id')->nullable()->after('deleted_at')
                ->constrained('users')->nullOnDelete();

            $table->text('deleted_reason')->nullable()->after('deleted_by_id');
        });
    }

    public function down(): void
    {
        Schema::table('data_pembanding', function (Blueprint $table) {
            $table->dropConstrainedForeignId('deleted_by_id');
            $table->dropColumn('deleted_reason');
        });
    }
};
