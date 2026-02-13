<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AnalyzedProduct;
use App\Services\API\IcgApiService;
use Carbon\Carbon;

class UpdateAnalyzedProductsFromIcg extends Command
{
    protected $signature = 'analysis:update-from-icg 
                            {--execution= : ID de la ejecución a actualizar}
                            {--status=not_found : Estado de match a actualizar (not_found)}
                            {--limit= : Limitar cantidad de productos a procesar}';

    protected $description = 'Buscar productos analizados en ICG y actualizar sus datos';

    protected $icgApi;

    public function __construct(IcgApiService $icgApi)
    {
        parent::__construct();
        $this->icgApi = $icgApi;
    }

    public function handle()
    {
        $this->info('=== ACTUALIZAR PRODUCTOS DESDE ICG ===');
        $this->newLine();

        // Construir query
        $query = AnalyzedProduct::query();

        if ($executionId = $this->option('execution')) {
            $query->where('analysis_execution_id', $executionId);
            $this->info("Filtrando por ejecución: {$executionId}");
        }

        if ($status = $this->option('status')) {
            $query->where('match_status', $status);
            $this->info("Filtrando por estado: {$status}");
        }

        if ($limit = $this->option('limit')) {
            $query->limit($limit);
            $this->info("Limitando a: {$limit} productos");
        }

        $products = $query->orderBy('id')->get();

        if ($products->isEmpty()) {
            $this->error('No se encontraron productos para actualizar');
            return 1;
        }

        $this->info("Total de productos a procesar: {$products->count()}");
        $this->newLine();

        if (!$this->confirm('¿Desea continuar?')) {
            $this->warn('Operación cancelada');
            return 0;
        }

        $this->newLine();

        $bar = $this->output->createProgressBar($products->count());
        $bar->start();

        $stats = [
            'processed' => 0,
            'found' => 0,
            'not_found' => 0,
            'errors' => 0,
        ];

        foreach ($products as $product) {
            try {
                $result = $this->icgApi->getProductBySku($product->magento_sku);

                if ($result['success'] && !empty($result['data'])) {
                    // Producto encontrado
                    $this->updateProductWithIcgData($product, $result['data']);
                    $stats['found']++;
                } else {
                    $stats['not_found']++;
                }

                $stats['processed']++;

            } catch (\Exception $e) {
                $this->newLine();
                $this->error("Error procesando SKU {$product->magento_sku}: " . $e->getMessage());
                $stats['errors']++;
            }

            $bar->advance();
            usleep(50000); // 0.05 segundos de pausa
        }

        $bar->finish();
        $this->newLine(2);

        // Mostrar estadísticas
        $this->info('=== RESUMEN ===');
        $this->table(
            ['Métrica', 'Cantidad'],
            [
                ['Procesados', $stats['processed']],
                ['Encontrados en ICG', $stats['found']],
                ['No encontrados', $stats['not_found']],
                ['Errores', $stats['errors']],
            ]
        );

        $this->newLine();
        $this->info('✓ Proceso completado');

        return 0;
    }

    protected function updateProductWithIcgData($product, $icgData)
    {
        $updateData = [
            'match_status' => 'exact',
            'match_score' => 100,
            'match_method' => 'exact',
            'icg_barcode' => $icgData['ARTEAN'] ?? null,
            'icg_articulo_id' => $icgData['ArticuloId'] ?? null,
            'icg_description' => $icgData['ARTDES'] ?? null,
            'icg_webname' => $icgData['WEBNAME'] ?? null,
            'icg_departamento' => $icgData['Departamento'] ?? null,
            'icg_seccion' => $icgData['Seccion'] ?? null,
            'icg_familia' => $icgData['Familia'] ?? null,
            'icg_marca' => $icgData['MARCA'] ?? null,
            'icg_price' => $icgData['PVPTARIF'] ?? null,
            'icg_offer_price' => $icgData['PVPOFER'] ?? null,
        ];

        // Analizar stock
        $stockData = $this->analyzeStock($icgData);
        $updateData = array_merge($updateData, $stockData);

        $product->update($updateData);
    }

    protected function analyzeStock($icgProduct)
    {
        $stockData = [
            'has_stock_b03' => false,
            'stock_b03' => 0,
            'last_purchase_b03' => null,
            'has_stock_b12' => false,
            'stock_b12' => 0,
            'last_purchase_b12' => null,
            'total_stock' => 0,
            'last_purchase_date' => null,
            'meets_stock_criteria' => false,
        ];

        $stocks = $icgProduct['Stocks'] ?? [];
        $totalStock = 0;
        $lastPurchases = [];

        // Fecha mínima: 6 meses atrás
        $minDate = Carbon::now()->subMonths(6);

        foreach ($stocks as $stock) {
            $almacenId = $stock['AlmacenId'] ?? null;
            $disponible = $stock['Disponible'] ?? 0;
            
            $totalStock += $disponible;

            // Stock B03
            if ($almacenId === 'B03') {
                $stockData['has_stock_b03'] = $disponible > 0;
                $stockData['stock_b03'] = $disponible;
                
                if (isset($stock['FechaUltimaCompra'])) {
                    try {
                        $fecha = Carbon::parse($stock['FechaUltimaCompra']);
                        if ($fecha->year >= 2000) {
                            $stockData['last_purchase_b03'] = $fecha;
                            $lastPurchases[] = $fecha;
                        }
                    } catch (\Exception $e) {
                        // Fecha inválida
                    }
                }
            }

            // Stock B12
            if ($almacenId === 'B12') {
                $stockData['has_stock_b12'] = $disponible > 0;
                $stockData['stock_b12'] = $disponible;
                
                if (isset($stock['FechaUltimaCompra'])) {
                    try {
                        $fecha = Carbon::parse($stock['FechaUltimaCompra']);
                        if ($fecha->year >= 2000) {
                            $stockData['last_purchase_b12'] = $fecha;
                            $lastPurchases[] = $fecha;
                        }
                    } catch (\Exception $e) {
                        // Fecha inválida
                    }
                }
            }
        }

        $stockData['total_stock'] = $totalStock;

        if (!empty($lastPurchases)) {
            $stockData['last_purchase_date'] = max($lastPurchases);
        }

        // Verificar criterios
        $meetsB03 = $stockData['has_stock_b03'] && 
                    $stockData['last_purchase_b03'] && 
                    $stockData['last_purchase_b03']->gte($minDate);
                    
        $meetsB12 = $stockData['has_stock_b12'] && 
                    $stockData['last_purchase_b12'] && 
                    $stockData['last_purchase_b12']->gte($minDate);

        $stockData['meets_stock_criteria'] = $meetsB03 || $meetsB12;

        return $stockData;
    }
}
