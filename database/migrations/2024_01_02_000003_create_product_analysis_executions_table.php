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
        Schema::create('product_analysis_executions', function (Blueprint $table) {
            $table->id();
            $table->string('job_id')->unique();
            $table->unsignedBigInteger('user_id');
            
            // Configuración del análisis
            $table->enum('analysis_type', ['no_images', 'stock_analysis', 'full_analysis'])->default('no_images');
            $table->json('filters')->nullable(); // Filtros aplicados
            $table->json('configuration_snapshot')->nullable(); // Configuración usada
            
            // Estado
            $table->enum('status', [
                'pending',
                'running',
                'completed',
                'completed_with_errors',
                'failed',
                'cancelled'
            ])->default('pending');
            
            // Tiempos
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('duration_seconds')->nullable();
            
            // Estadísticas
            $table->integer('total_magento_products')->default(0);
            $table->integer('products_without_images')->default(0);
            $table->integer('products_matched')->default(0);
            $table->integer('products_with_stock')->default(0);
            $table->integer('products_meeting_criteria')->default(0);
            $table->integer('errors_count')->default(0);
            
            // Resultados
            $table->text('result_message')->nullable();
            $table->json('error_details')->nullable();
            $table->string('export_filename')->nullable();
            $table->string('export_path')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            $table->index('status');
            $table->index('analysis_type');
            $table->index('started_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_analysis_executions');
    }
};
