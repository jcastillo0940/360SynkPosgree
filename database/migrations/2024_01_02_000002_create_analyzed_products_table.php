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
        Schema::create('analyzed_products', function (Blueprint $table) {
            $table->id();
            
            // Información de Magento
            $table->string('magento_sku')->index();
            $table->string('magento_name')->nullable();
            $table->string('magento_description', 1000)->nullable();
            $table->boolean('has_image')->default(false);
            $table->integer('magento_category_id')->nullable();
            $table->string('magento_category_name')->nullable();
            
            // Información de ICG
            $table->string('icg_barcode')->nullable()->index();
            $table->integer('icg_articulo_id')->nullable()->index();
            $table->string('icg_description')->nullable();
            $table->string('icg_webname')->nullable();
            $table->string('icg_departamento')->nullable();
            $table->string('icg_seccion')->nullable();
            $table->string('icg_familia')->nullable();
            $table->string('icg_marca')->nullable();
            
            // Matching/Coincidencia
            $table->enum('match_status', ['not_found', 'exact', 'approximate', 'manual'])->default('not_found');
            $table->decimal('match_score', 5, 2)->nullable(); // Porcentaje de similitud 0-100
            $table->string('match_method')->nullable(); // 'barcode', 'description', 'manual'
            $table->text('match_details')->nullable(); // JSON con detalles del match
            
            // Stock Information
            $table->boolean('has_stock_b03')->default(false);
            $table->integer('stock_b03')->default(0);
            $table->date('last_purchase_b03')->nullable();
            
            $table->boolean('has_stock_b12')->default(false);
            $table->integer('stock_b12')->default(0);
            $table->date('last_purchase_b12')->nullable();
            
            $table->integer('total_stock')->default(0);
            $table->date('last_purchase_date')->nullable();
            $table->boolean('meets_stock_criteria')->default(false);
            
            // Precios
            $table->decimal('icg_price', 10, 2)->nullable();
            $table->decimal('icg_offer_price', 10, 2)->nullable();
            $table->date('icg_offer_from')->nullable();
            $table->date('icg_offer_to')->nullable();
            
            // Analysis metadata
            $table->unsignedBigInteger('analysis_execution_id')->nullable();
            $table->timestamp('analyzed_at');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('status', ['pending', 'reviewed', 'approved', 'rejected'])->default('pending');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign keys
            $table->foreign('analysis_execution_id')->references('id')->on('product_analysis_executions')->onDelete('set null');
            $table->foreign('reviewed_by')->references('id')->on('users')->onDelete('set null');
            
            // Indexes
            $table->index('has_image');
            $table->index('match_status');
            $table->index('status');
            $table->index('priority');
            $table->index('meets_stock_criteria');
            $table->index('analyzed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analyzed_products');
    }
};