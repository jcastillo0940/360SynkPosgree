<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AnalysisConfiguration;
use Illuminate\Support\Facades\DB;

class AnalysisConfigurationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('analysis_configurations')->truncate();

        $configurations = [
            // Configuraciones Generales
            [
                'key' => 'min_match_score',
                'category' => 'general',
                'label' => 'Score Mínimo de Coincidencia (%)',
                'value' => '60',
                'type' => 'integer',
                'description' => 'Porcentaje mínimo de similitud entre descripciones para considerar una coincidencia válida',
                'is_required' => true,
                'is_visible' => true,
                'display_order' => 1,
                'validation_rules' => json_encode(['min:0', 'max:100']),
                'default_value' => '60',
            ],
            [
                'key' => 'enable_fuzzy_matching',
                'category' => 'general',
                'label' => 'Habilitar Coincidencia Aproximada',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Permite buscar productos por similitud de descripción cuando no hay coincidencia exacta por SKU',
                'is_required' => false,
                'is_visible' => true,
                'display_order' => 2,
                'default_value' => 'true',
            ],
            [
                'key' => 'auto_approve_exact_match',
                'category' => 'general',
                'label' => 'Auto-aprobar Coincidencias Exactas',
                'value' => 'false',
                'type' => 'boolean',
                'description' => 'Aprobar automáticamente productos con coincidencia exacta por SKU',
                'is_required' => false,
                'is_visible' => true,
                'display_order' => 3,
                'default_value' => 'false',
            ],

            // Configuraciones de Stock
            [
                'key' => 'min_stock_months',
                'category' => 'stock',
                'label' => 'Meses Mínimos desde Última Compra',
                'value' => '6',
                'type' => 'integer',
                'description' => 'Número de meses máximo desde la última compra para considerar el stock como válido',
                'is_required' => true,
                'is_visible' => true,
                'display_order' => 10,
                'validation_rules' => json_encode(['min:1', 'max:36']),
                'default_value' => '6',
            ],
            [
                'key' => 'require_stock_b03',
                'category' => 'stock',
                'label' => 'Requerir Stock en Almacén B03',
                'value' => 'false',
                'type' => 'boolean',
                'description' => 'El producto debe tener stock disponible en el almacén B03',
                'is_required' => false,
                'is_visible' => true,
                'display_order' => 11,
                'default_value' => 'false',
            ],
            [
                'key' => 'require_stock_b12',
                'category' => 'stock',
                'label' => 'Requerir Stock en Almacén B12',
                'value' => 'false',
                'type' => 'boolean',
                'description' => 'El producto debe tener stock disponible en el almacén B12',
                'is_required' => false,
                'is_visible' => true,
                'display_order' => 12,
                'default_value' => 'false',
            ],
            [
                'key' => 'min_stock_quantity',
                'category' => 'stock',
                'label' => 'Cantidad Mínima de Stock',
                'value' => '1',
                'type' => 'integer',
                'description' => 'Cantidad mínima de unidades en stock para considerar el producto válido',
                'is_required' => false,
                'is_visible' => true,
                'display_order' => 13,
                'validation_rules' => json_encode(['min:0']),
                'default_value' => '1',
            ],

            // Configuraciones de Matching
            [
                'key' => 'match_by_description',
                'category' => 'matching',
                'label' => 'Buscar por Descripción',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Comparar descripciones de productos para encontrar coincidencias',
                'is_required' => false,
                'is_visible' => true,
                'display_order' => 20,
                'default_value' => 'true',
            ],
            [
                'key' => 'match_by_webname',
                'category' => 'matching',
                'label' => 'Buscar por WEBNAME',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Comparar con el campo WEBNAME de ICG',
                'is_required' => false,
                'is_visible' => true,
                'display_order' => 21,
                'default_value' => 'true',
            ],
            [
                'key' => 'normalize_strings',
                'category' => 'matching',
                'label' => 'Normalizar Strings',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Normalizar texto (minúsculas, sin acentos, sin caracteres especiales) antes de comparar',
                'is_required' => false,
                'is_visible' => true,
                'display_order' => 22,
                'default_value' => 'true',
            ],
            [
                'key' => 'substring_match_bonus',
                'category' => 'matching',
                'label' => 'Bonificación por Substring',
                'value' => '15',
                'type' => 'integer',
                'description' => 'Puntos adicionales de score cuando un string está contenido en otro',
                'is_required' => false,
                'is_visible' => true,
                'display_order' => 23,
                'validation_rules' => json_encode(['min:0', 'max:50']),
                'default_value' => '15',
            ],

            // Configuraciones de Magento
            [
                'key' => 'magento_page_size',
                'category' => 'magento',
                'label' => 'Productos por Página (Magento)',
                'value' => '100',
                'type' => 'integer',
                'description' => 'Número de productos a obtener por página desde Magento API',
                'is_required' => false,
                'is_visible' => true,
                'display_order' => 30,
                'validation_rules' => json_encode(['min:10', 'max:500']),
                'default_value' => '100',
            ],
            [
                'key' => 'magento_max_pages',
                'category' => 'magento',
                'label' => 'Máximo de Páginas (Magento)',
                'value' => '50',
                'type' => 'integer',
                'description' => 'Límite de páginas a procesar de Magento (seguridad)',
                'is_required' => false,
                'is_visible' => true,
                'display_order' => 31,
                'validation_rules' => json_encode(['min:1', 'max:1000']),
                'default_value' => '50',
            ],

            // Configuraciones de ICG
            [
                'key' => 'icg_page_size',
                'category' => 'icg',
                'label' => 'Productos por Página (ICG)',
                'value' => '100',
                'type' => 'integer',
                'description' => 'Número de productos a obtener por página desde ICG API',
                'is_required' => false,
                'is_visible' => true,
                'display_order' => 40,
                'validation_rules' => json_encode(['min:10', 'max:500']),
                'default_value' => '100',
            ],
            [
                'key' => 'icg_max_pages',
                'category' => 'icg',
                'label' => 'Máximo de Páginas (ICG)',
                'value' => '100',
                'type' => 'integer',
                'description' => 'Límite de páginas a procesar de ICG (seguridad)',
                'is_required' => false,
                'is_visible' => true,
                'display_order' => 41,
                'validation_rules' => json_encode(['min:1', 'max:1000']),
                'default_value' => '100',
            ],
            [
                'key' => 'icg_filter_invalid_dates',
                'category' => 'icg',
                'label' => 'Filtrar Fechas Inválidas',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Ignorar fechas de compra anteriores al año 2000',
                'is_required' => false,
                'is_visible' => true,
                'display_order' => 42,
                'default_value' => 'true',
            ],

            // Configuraciones de Reportes
            [
                'key' => 'report_include_no_match',
                'category' => 'reports',
                'label' => 'Incluir Productos Sin Coincidencia',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Incluir productos que no tienen coincidencia en ICG en los reportes',
                'is_required' => false,
                'is_visible' => true,
                'display_order' => 50,
                'default_value' => 'true',
            ],
            [
                'key' => 'report_include_no_stock',
                'category' => 'reports',
                'label' => 'Incluir Productos Sin Stock',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Incluir productos sin stock en los reportes',
                'is_required' => false,
                'is_visible' => true,
                'display_order' => 51,
                'default_value' => 'true',
            ],
            [
                'key' => 'auto_export_csv',
                'category' => 'reports',
                'label' => 'Auto-exportar CSV',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Generar archivo CSV automáticamente al finalizar análisis',
                'is_required' => false,
                'is_visible' => true,
                'display_order' => 52,
                'default_value' => 'true',
            ],
        ];

        foreach ($configurations as $config) {
            AnalysisConfiguration::create($config);
        }

        $this->command->info('✓ Configuraciones de análisis creadas exitosamente');
        $this->command->info('  Total de configuraciones: ' . count($configurations));
    }
}
