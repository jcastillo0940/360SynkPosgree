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
        Schema::create('analysis_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('category'); // 'general', 'stock', 'matching', 'magento', 'icg'
            $table->string('label');
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, integer, boolean, date, json
            $table->text('description')->nullable();
            $table->boolean('is_required')->default(false);
            $table->boolean('is_visible')->default(true);
            $table->integer('display_order')->default(0);
            $table->json('validation_rules')->nullable();
            $table->text('default_value')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('last_tested_at')->nullable();
            $table->boolean('test_passed')->nullable();
            $table->timestamps();

            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->index('category');
            $table->index('key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analysis_configurations');
    }
};