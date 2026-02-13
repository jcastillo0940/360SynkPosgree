<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('sales_orders_raw', function (Blueprint $table) {
        $table->id();
        $table->string('magento_order_id')->unique();
        $table->string('increment_id');
        $table->unsignedBigInteger('customer_id')->index();
        $table->decimal('grand_total', 15, 2);
        $table->string('store_code')->nullable();
        $table->dateTime('order_created_at')->index();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_orders_raw');
    }
};
