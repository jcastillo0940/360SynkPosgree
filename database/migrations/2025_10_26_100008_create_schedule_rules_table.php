<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('workflow_id')->constrained('workflows')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            
            $table->enum('action', ['total', 'partial'])->default('total');
            $table->text('skus')->nullable();
            
            $table->enum('frequency', ['daily', 'weekly', 'monthly'])->default('daily');
            $table->time('execution_time');
            $table->integer('day_of_week')->nullable();
            $table->integer('day_of_month')->nullable();
            
            $table->boolean('is_enabled')->default(true);
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->integer('run_count')->default(0);
            
            $table->json('configuration')->nullable();
            $table->text('description')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['workflow_id', 'is_enabled']);
            $table->index('next_run_at');
            $table->index(['execution_time', 'is_enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_rules');
    }
};
