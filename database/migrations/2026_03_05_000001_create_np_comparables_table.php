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
        Schema::create('np_comparables', function (Blueprint $table) {
            $table->id();
            $table->string('comparable_code', 50)->nullable()->unique();
            $table->string('asset_category', 50)->index();
            $table->string('asset_subtype', 100)->nullable();
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('variant')->nullable();
            $table->unsignedSmallInteger('manufacture_year')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('registration_number')->nullable();

            $table->string('listing_type', 50)->nullable();
            $table->string('source_platform')->nullable();
            $table->string('source_name')->nullable();
            $table->string('source_phone', 64)->nullable();
            $table->text('source_url')->nullable();

            $table->string('location_country')->nullable();
            $table->string('location_city')->nullable();
            $table->text('location_address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('distance_to_subject_km', 10, 2)->nullable();

            $table->char('currency', 3)->default('IDR');
            $table->decimal('asking_price', 18, 2)->nullable();
            $table->decimal('transaction_price', 18, 2)->nullable();
            $table->decimal('assumed_discount_percent', 5, 2)->nullable();
            $table->date('data_date')->nullable();

            $table->string('asset_condition', 50)->nullable();
            $table->string('operational_status', 50)->nullable();
            $table->string('legal_document_status')->nullable();
            $table->enum('verification_status', ['unverified', 'partial', 'verified'])
                ->default('unverified')
                ->index();
            $table->unsignedTinyInteger('confidence_score')->nullable();
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['asset_category', 'data_date'], 'np_cmp_asset_data_date_idx');
            $table->index(['brand', 'model', 'manufacture_year'], 'np_cmp_brand_model_year_idx');
            $table->index(['location_country', 'location_city'], 'np_cmp_country_city_idx');
            $table->index('asking_price', 'np_cmp_asking_price_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('np_comparables');
    }
};
