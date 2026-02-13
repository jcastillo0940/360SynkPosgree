<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('magento_sources', function (Blueprint $table) {
            $table->id();
            $table->string('source_code')->unique(); // 1, 12, 13, etc.
            $table->string('source_name'); // Aguadulce, Albrook, etc.
            $table->boolean('is_enabled')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Insertar tus sucursales
        DB::table('magento_sources')->insert([
            ['source_code' => '1', 'source_name' => 'Aguadulce', 'is_enabled' => true, 'is_active' => true],
            ['source_code' => '12', 'source_name' => 'Albrook', 'is_enabled' => true, 'is_active' => true],
            ['source_code' => '13', 'source_name' => 'Costa Verde', 'is_enabled' => true, 'is_active' => true],
            ['source_code' => '6', 'source_name' => 'Canto Del Llano (Santiago)', 'is_enabled' => false, 'is_active' => false],
            ['source_code' => '7', 'source_name' => 'Las Tablas', 'is_enabled' => true, 'is_active' => true],
            ['source_code' => '10', 'source_name' => 'Chitré', 'is_enabled' => true, 'is_active' => true],
            ['source_code' => '8', 'source_name' => 'Penonomé', 'is_enabled' => true, 'is_active' => true],
            ['source_code' => '11', 'source_name' => 'La Chorrera', 'is_enabled' => true, 'is_active' => true],
            ['source_code' => '2', 'source_name' => 'Central (Santiago)', 'is_enabled' => false, 'is_active' => false],
            ['source_code' => '3', 'source_name' => 'Santiago', 'is_enabled' => true, 'is_active' => true],
            ['source_code' => '4', 'source_name' => 'Mercado (Santiago)', 'is_enabled' => false, 'is_active' => false],
            ['source_code' => '5', 'source_name' => 'Terminal (Santiago)', 'is_enabled' => false, 'is_active' => false],
            ['source_code' => '9', 'source_name' => 'Arraiján', 'is_enabled' => true, 'is_active' => true],
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('magento_sources');
    }
};