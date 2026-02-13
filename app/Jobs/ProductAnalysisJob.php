<?php

namespace App\Jobs;

use App\Models\ProductAnalysisExecution;
use App\Models\AnalyzedProduct;
use App\Models\AnalysisConfiguration;
use App\Services\API\MagentoApiService;
use App\Services\API\IcgApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ProductAnalysisJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 7200; // 2 horas
    public $tries = 1;

    protected $execution;

    public function __construct(ProductAnalysisExecution $execution)
    {
        $this->execution = $execution;
    }

    public function handle()
    {
        try {
            $this->execution->markAsStarted();
            $this->log('INFO', 'Iniciando análisis de productos');

            // Obtener configuraciones dinámicas
            $config = $this->getConfiguration();
            $this->log('INFO', 'Configuración cargada: ' . json_encode($config));

            // Inicializar servicios de API
            $magentoApi = app(MagentoApiService::class);
            $icgApi = app(IcgApiService::class);

            // Paso 1: Obtener productos de Magento sin imágenes
            $magentoProducts = $this->getMagentoProductsWithoutImages($magentoApi);
            
            $this->execution->update([
                'total_magento_products' => count($magentoProducts),
                'products_without_images' => count($magentoProducts)
            ]);

            if (empty($magentoProducts)) {
                $this->execution->markAsCompleted('completed', 'No se encontraron productos sin imágenes');
                $this->log('INFO', 'Análisis completado: No hay productos para procesar');
                return;
            }

            $this->log('SUCCESS', "Encontrados {$this->execution->products_without_images} productos para analizar");

            // Paso 2: Procesar cada producto de Magento contra ICG
            $processed = 0;
            $matched = 0;
            $withStock = 0;
            $meetingCriteria = 0;

            foreach ($magentoProducts as $magentoProduct) {
                $processed++;
                $progress = round(($processed / count($magentoProducts)) * 100, 2);

                // Log de progreso
                if ($processed % 50 == 0 || $processed == 1) {
                    $this->log('INFO', "Procesando {$processed}/{$this->execution->products_without_images} ({$progress}%)", null, $magentoProduct['sku'], $progress);
                }

                // Buscar coincidencia en ICG API directamente
                $matchResult = $this->findMatchInIcg($magentoProduct, $config);

                // Crear registro de producto analizado en DB local
                $analyzedProduct = $this->createAnalyzedProduct(
                    $magentoProduct,
                    $matchResult,
                    $config
                );

                if ($matchResult['matched']) {
                    $matched++;
                    if ($analyzedProduct->meets_stock_criteria) $meetingCriteria++;
                    if ($analyzedProduct->total_stock > 0) $withStock++;
                }

                // Actualizar estadísticas de ejecución periódicamente
                if ($processed % 20 == 0) {
                    $this->execution->update([
                        'products_matched' => $matched,
                        'products_with_stock' => $withStock,
                        'products_meeting_criteria' => $meetingCriteria,
                    ]);
                }
            }

            // Estadísticas finales
            $this->execution->update([
                'products_matched' => $matched,
                'products_with_stock' => $withStock,
                'products_meeting_criteria' => $meetingCriteria,
            ]);

            // Paso 3: Generar reporte CSV final
            $this->log('INFO', 'Generando reporte CSV...');
            $reportPath = $this->generateReport();
            
            if ($reportPath) {
                $this->execution->update([
                    'export_filename' => basename($reportPath),
                    'export_path' => $reportPath,
                ]);
            }

            $this->execution->markAsCompleted(
                'completed',
                "Análisis completado: {$matched} productos vinculados con ICG, {$meetingCriteria} cumplen criterios de stock activo."
            );

            $this->log('SUCCESS', 'Análisis finalizado exitosamente');

        } catch (\Exception $e) {
            $this->log('ERROR', 'Error crítico en Job: ' . $e->getMessage());
            $this->execution->markAsFailed($e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            throw $e;
        }
    }

    protected function getConfiguration()
    {
        return [
            'min_stock_months' => AnalysisConfiguration::get('min_stock_months', 6),
            'min_match_score' => AnalysisConfiguration::get('min_match_score', 60),
            'warehouses' => ['B03', 'B12'],
            'enable_fuzzy_matching' => AnalysisConfiguration::get('enable_fuzzy_matching', true),
        ];
    }

    protected function getMagentoProductsWithoutImages($magentoApi)
    {
        $productsWithoutImages = [];
        $page = 1;
        $pageSize = AnalysisConfiguration::get('magento_page_size', 100);
        $maxPages = AnalysisConfiguration::get('magento_max_pages', 50);

        while ($page <= $maxPages) {
            $response = $magentoApi->getProducts($page, $pageSize);
            
            if (empty($response['items'])) break;

            foreach ($response['items'] as $product) {
                if ($this->productHasNoImages($product)) {
                    $productsWithoutImages[] = [
                        'sku' => $product['sku'],
                        'name' => $product['name'] ?? '',
                        'description' => $this->extractDescription($product),
                        'category_id' => $this->extractCategoryId($product),
                        'category_name' => $this->extractCategoryName($product),
                    ];
                }
            }
            $this->log('INFO', "Escaneando Magento: Página {$page} analizada...");
            $page++;
            usleep(100000); // 0.1s de respiro para la API
        }
        return $productsWithoutImages;
    }

    protected function productHasNoImages($product)
    {
        // Check gallery
        if (!empty($product['media_gallery_entries'])) {
            foreach ($product['media_gallery_entries'] as $media) {
                if (($media['media_type'] ?? '') === 'image' && !($media['disabled'] ?? false)) {
                    return false; 
                }
            }
        }
        // Check core attributes
        if (!empty($product['custom_attributes'])) {
            foreach ($product['custom_attributes'] as $attr) {
                if (in_array($attr['attribute_code'], ['image', 'small_image', 'thumbnail'])) {
                    if (!empty($attr['value']) && $attr['value'] !== 'no_selection') {
                        return false;
                    }
                }
            }
        }
        return true;
    }

    protected function findMatchInIcg($magentoProduct, $config)
    {
        $sku = $magentoProduct['sku'];
        $icgApi = app(IcgApiService::class);
        
        try {
            $result = $icgApi->getProductBySku($sku);
            if ($result['success'] && !empty($result['data'])) {
                return [
                    'matched' => true,
                    'match_type' => 'exact',
                    'match_score' => 100,
                    'icg_product' => $result['data'],
                ];
            }
        } catch (\Exception $e) {
            $this->log('WARNING', "Error API ICG para SKU {$sku}: " . $e->getMessage());
        }

        return ['matched' => false, 'match_type' => 'not_found', 'match_score' => 0, 'icg_product' => null];
    }

    protected function createAnalyzedProduct($magentoProduct, $matchResult, $config)
    {
        $data = [
            'analysis_execution_id' => $this->execution->id,
            'analyzed_at' => now(),
            'magento_sku' => $magentoProduct['sku'],
            'magento_name' => $magentoProduct['name'],
            'magento_description' => $magentoProduct['description'],
            'has_image' => false,
            'match_status' => $matchResult['match_type'],
            'match_score' => $matchResult['match_score'],
            'match_method' => $matchResult['match_type'],
        ];

        if ($matchResult['matched']) {
            $icg = $matchResult['icg_product'];
            $data = array_merge($data, [
                'icg_barcode' => $icg['ARTEAN'] ?? null,
                'icg_articulo_id' => $icg['ArticuloId'] ?? null,
                'icg_description' => $icg['Descripcion'] ?? null,
                'icg_webname' => $icg['WEBNAME'] ?? null,
                'icg_departamento' => $icg['Departamento'] ?? null,
                'icg_seccion' => $icg['Seccion'] ?? null,
                'icg_familia' => $icg['Familia'] ?? null,
                'icg_marca' => $icg['Marca'] ?? null,
                'icg_price' => $icg['Precio'] ?? null,
                'icg_offer_price' => $icg['PrecioOferta'] ?? null,
            ]);
            
            $stockAnalysis = $this->analyzeStock($icg, $config);
            $data = array_merge($data, $stockAnalysis);
        }

        return AnalyzedProduct::create($data);
    }

    protected function analyzeStock($icgProduct, $config)
    {
        $res = [
            'has_stock_b03' => false, 'stock_b03' => 0, 'last_purchase_b03' => null,
            'has_stock_b12' => false, 'stock_b12' => 0, 'last_purchase_b12' => null,
            'total_stock' => 0, 'last_purchase_date' => null, 'meets_stock_criteria' => false
        ];

        $minDate = Carbon::now()->subMonths($config['min_stock_months']);
        $tallasColores = $icgProduct['TallasColores'] ?? [];

        foreach ($tallasColores as $tc) {
            $stock = $tc['Disponible'] ?? 0;
            $res['total_stock'] += $stock;
            $fecha = $this->parseIcgDate($tc['FechaUltimaCompra'] ?? null);

            if ($tc['AlmacenId'] === 'B03') {
                $res['has_stock_b03'] = $stock > 0;
                $res['stock_b03'] = $stock;
                $res['last_purchase_b03'] = $fecha;
            }
            if ($tc['AlmacenId'] === 'B12') {
                $res['has_stock_b12'] = $stock > 0;
                $res['stock_b12'] = $stock;
                $res['last_purchase_b12'] = $fecha;
            }
        }

        $res['last_purchase_date'] = collect([$res['last_purchase_b03'], $res['last_purchase_b12']])->filter()->max();
        
        $meetsB03 = ($res['stock_b03'] > 0 && $res['last_purchase_b03']?->gte($minDate));
        $meetsB12 = ($res['stock_b12'] > 0 && $res['last_purchase_b12']?->gte($minDate));
        $res['meets_stock_criteria'] = ($meetsB03 || $meetsB12);

        return $res;
    }

    protected function parseIcgDate($dateStr)
    {
        if (!$dateStr) return null;
        try {
            $date = Carbon::parse($dateStr);
            return ($date->year >= 2000) ? $date : null;
        } catch (\Exception $e) { return null; }
    }

    protected function extractDescription($product)
    {
        if (empty($product['custom_attributes'])) return '';
        foreach ($product['custom_attributes'] as $attr) {
            if ($attr['attribute_code'] == 'description') return strip_tags($attr['value'] ?? '');
        }
        return '';
    }

    protected function extractCategoryId($product)
    {
        return $product['extension_attributes']['category_links'][0]['category_id'] ?? null;
    }

    protected function extractCategoryName($product) { return null; }

    protected function generateReport()
    {
        $products = $this->execution->analyzedProducts()->orderBy('meets_stock_criteria', 'desc')->get();
        if ($products->isEmpty()) return null;

        $filename = "analysis_{$this->execution->id}_" . date('YmdHis') . ".csv";
        $path = storage_path("app/exports/{$filename}");
        
        if (!file_exists(dirname($path))) mkdir(dirname($path), 0755, true);

        $file = fopen($path, 'w');
        fputcsv($file, ['SKU Magento', 'Nombre', 'Match', 'Score', 'SKU ICG', 'Marca', 'Stock B03', 'Stock B12', 'Cumple Criterio', 'Precio']);
        
        foreach ($products as $p) {
            fputcsv($file, [
                $p->magento_sku, $p->magento_name, $p->match_status, $p->match_score, 
                $p->icg_barcode, $p->icg_marca, $p->stock_b03, $p->stock_b12, 
                $p->meets_stock_criteria ? 'SI' : 'NO', $p->icg_price
            ]);
        }
        fclose($file);
        return $path;
    }

    protected function log($level, $message, $context = null, $sku = null, $progress = null)
    {
        $this->execution->addLog($level, $message, $context, $sku, $progress);
    }
}