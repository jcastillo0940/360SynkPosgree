<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('sales_analysis_periods', function (Blueprint $table) {
            $table->id();
            $table->string('period_type'); // daily, weekly, monthly, quarterly, yearly
            $table->date('period_start');
            $table->date('period_end');
            $table->string('status')->default('pending'); // pending, processing, completed, failed
            
            // KPIs Generales
            $table->integer('total_orders')->default(0);
            $table->decimal('total_revenue', 15, 2)->default(0);
            $table->decimal('average_order_value', 10, 2)->default(0);
            $table->integer('total_items_sold')->default(0);
            $table->integer('total_customers')->default(0);
            $table->integer('new_customers')->default(0);
            $table->integer('returning_customers')->default(0);
            
            // KPIs de Conversión
            $table->integer('total_sessions')->nullable();
            $table->decimal('conversion_rate', 5, 2)->nullable();
            $table->integer('carts_created')->default(0);
            $table->integer('carts_abandoned')->default(0);
            $table->decimal('cart_abandonment_rate', 5, 2)->default(0);
            $table->integer('carts_recovered')->default(0);
            $table->decimal('cart_recovery_rate', 5, 2)->default(0);
            
            // KPIs de Retención
            $table->integer('repeat_customers')->default(0);
            $table->decimal('repeat_purchase_rate', 5, 2)->default(0);
            $table->decimal('customer_retention_rate', 5, 2)->default(0);
            $table->decimal('churn_rate', 5, 2)->default(0);
            $table->decimal('customer_lifetime_value', 10, 2)->default(0);
            
            // Metadata
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->json('additional_data')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['period_type', 'period_start']);
            $table->index('status');
        });

        Schema::create('sales_by_store', function (Blueprint $table) {
            $table->id();
            $table->foreignId('period_id')->constrained('sales_analysis_periods')->onDelete('cascade');
            $table->string('store_code');
            $table->string('store_name');
            
            $table->integer('total_orders')->default(0);
            $table->decimal('total_revenue', 15, 2)->default(0);
            $table->decimal('average_order_value', 10, 2)->default(0);
            $table->integer('total_items_sold')->default(0);
            
            $table->timestamps();
            
            $table->index('store_code');
        });

        Schema::create('sales_by_payment_method', function (Blueprint $table) {
            $table->id();
            $table->foreignId('period_id')->constrained('sales_analysis_periods')->onDelete('cascade');
            $table->string('payment_method');
            $table->string('payment_method_title')->nullable();
            
            $table->integer('total_orders')->default(0);
            $table->decimal('total_revenue', 15, 2)->default(0);
            $table->decimal('percentage', 5, 2)->default(0);
            
            $table->timestamps();
            
            $table->index('payment_method');
        });

        Schema::create('sales_by_location', function (Blueprint $table) {
            $table->id();
            $table->foreignId('period_id')->constrained('sales_analysis_periods')->onDelete('cascade');
            $table->string('country')->nullable();
            $table->string('region')->nullable();
            $table->string('city')->nullable();
            $table->string('postcode')->nullable();
            
            $table->integer('total_orders')->default(0);
            $table->decimal('total_revenue', 15, 2)->default(0);
            $table->integer('total_customers')->default(0);
            
            $table->timestamps();
            
           
			$table->index('country');
			$table->index('region');
			$table->index('city');
        });

        Schema::create('top_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('period_id')->constrained('sales_analysis_periods')->onDelete('cascade');
            $table->string('sku');
            $table->string('product_name');
            
            $table->integer('quantity_sold')->default(0);
            $table->decimal('total_revenue', 15, 2)->default(0);
            $table->integer('times_ordered')->default(0);
            $table->decimal('average_price', 10, 2)->default(0);
            
            $table->timestamps();
            
            $table->index('sku');
        });

        Schema::create('customer_cohorts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('period_id')->constrained('sales_analysis_periods')->onDelete('cascade');
            $table->date('cohort_month'); // Mes de primera compra
            
            $table->integer('customers_count')->default(0);
            $table->decimal('initial_revenue', 15, 2)->default(0);
            $table->json('retention_by_month')->nullable(); // Array con retención mes a mes
            
            $table->timestamps();
            
            $table->index('cohort_month');
        });
    }

    public function down()
    {
        Schema::dropIfExists('customer_cohorts');
        Schema::dropIfExists('top_products');
        Schema::dropIfExists('sales_by_location');
        Schema::dropIfExists('sales_by_payment_method');
        Schema::dropIfExists('sales_by_store');
        Schema::dropIfExists('sales_analysis_periods');
    }
};