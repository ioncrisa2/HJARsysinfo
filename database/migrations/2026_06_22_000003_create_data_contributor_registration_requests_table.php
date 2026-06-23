<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_contributor_registration_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invite_id')->constrained('data_contributor_invites')->cascadeOnDelete();
            $table->string('display_name');
            $table->string('generated_email')->unique();
            $table->string('phone');
            $table->string('password_hash');
            $table->string('status', 32)->default('pending')->index();
            $table->timestamp('submitted_at');
            $table->timestamp('accepted_at')->nullable();
            $table->foreignId('accepted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reject_reason')->nullable();
            $table->timestamps();

            $table->index(['phone', 'status']);
            $table->index(['invite_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_contributor_registration_requests');
    }
};
