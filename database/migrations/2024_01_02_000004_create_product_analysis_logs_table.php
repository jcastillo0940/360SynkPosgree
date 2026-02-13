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
        Schema::create('product_analysis_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('analysis_execution_id');
            $table->enum('level', ['DEBUG', 'INFO', 'SUCCESS', 'WARNING', 'ERROR', 'CRITICAL'])->default('INFO');
            $table->text('message');
            $table->json('context')->nullable();
            $table->string('sku')->nullable();
            $table->integer('current_step')->nullable();
            $table->integer('total_steps')->nullable();
            $table->decimal('progress_percentage', 5, 2)->nullable();
            $table->timestamp('logged_at');
            
            $table->foreign('analysis_execution_id')
                ->references('id')
                ->on('product_analysis_executions')
                ->onDelete('cascade');
            
            $table->index('analysis_execution_id');
            $table->index('level');
            $table->index('logged_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_analysis_logs');
    }
};
