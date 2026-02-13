<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\API\MagentoApiService;
use Illuminate\Support\Facades\Log;

class TestMagentoApi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'magento:test 
                            {--sku= : Test specific product by SKU}
                            {--limit=5 : Number of products to fetch}
                            {--page=1 : Page number}
                            {--details : Show full product details}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Magento API and display product data structure';

    protected $magentoService;

    public function __construct(MagentoApiService $magentoService)
    {
        parent::__construct();
        $this->magentoService = $magentoService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== MAGENTO API TEST ===');
        $this->newLine();

        // Test específico por SKU
        if ($this->option('sku')) {
            $this->testProductBySku($this->option('sku'));
            return;
        }

        // Test general de productos
        $this->testProductsList();
    }

    /**
     * Test producto específico por SKU
     */
    private function testProductBySku($sku)
    {
        $this->info("Buscando producto con SKU: {$sku}");
        $this->newLine();

        try {
            $product = $this->magentoService->getProductBySku($sku);

            if ($product) {
                $this->line('? Producto encontrado:');
                $this->displayProductInfo($product, true);
            } else {
                $this->error("? Producto con SKU '{$sku}' no encontrado");
            }
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
        }
    }

    /**
     * Test lista de productos
     */
    private function testProductsList()
    {
        $page = (int) $this->option('page');
        $limit = (int) $this->option('limit');
        $showDetails = $this->option('details');

        $this->info("Obteniendo {$limit} productos (Página {$page})...");
        $this->newLine();

        try {
            $response = $this->magentoService->getProducts($page, $limit);
            
            $this->info('=== RESPUESTA DE LA API ===');
            $this->line('Total encontrado: ' . ($response['total_count'] ?? 0));
            $this->line('Productos en esta página: ' . count($response['items'] ?? []));
            $this->newLine();

            if (empty($response['items'])) {
                $this->warn('? No se encontraron productos');
                return;
            }

            // Analizar estructura de imágenes
            $this->analyzeImageStructure($response['items']);

            // Mostrar productos
            foreach ($response['items'] as $index => $product) {
                $this->line("--- PRODUCTO #" . ($index + 1) . " ---");
                $this->displayProductInfo($product, $showDetails);
                $this->newLine();
            }

        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            Log::error('Magento API Test Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Mostrar información del producto
     */
    private function displayProductInfo($product, $showFullDetails = false)
    {
        // Información básica
        $this->line("SKU: " . ($product['sku'] ?? 'N/A'));
        $this->line("Nombre: " . ($product['name'] ?? 'N/A'));
        $this->line("Tipo: " . ($product['type_id'] ?? 'N/A'));
        
        // Analizar imágenes
        $imageInfo = $this->analyzeProductImages($product);
        
        if ($imageInfo['has_images']) {
            $this->line("<fg=green>? TIENE IMÁGENES</>");
        } else {
            $this->line("<fg=red>? SIN IMÁGENES</>");
        }

        $this->line("  - media_gallery_entries: " . $imageInfo['media_gallery_count']);
        $this->line("  - image attribute: " . ($imageInfo['image_attr'] ?: 'no_selection'));
        $this->line("  - small_image attribute: " . ($imageInfo['small_image_attr'] ?: 'no_selection'));
        $this->line("  - thumbnail attribute: " . ($imageInfo['thumbnail_attr'] ?: 'no_selection'));

        // Mostrar detalles completos si se solicita
        if ($showFullDetails) {
            $this->newLine();
            $this->line("=== ESTRUCTURA COMPLETA ===");
            
            // Media Gallery
            if (isset($product['media_gallery_entries'])) {
                $this->line("\n?? MEDIA GALLERY ENTRIES:");
                foreach ($product['media_gallery_entries'] as $idx => $media) {
                    $this->line("  [{$idx}] Type: " . ($media['media_type'] ?? 'N/A'));
                    $this->line("      File: " . ($media['file'] ?? 'N/A'));
                    $this->line("      Disabled: " . (($media['disabled'] ?? false) ? 'Yes' : 'No'));
                }
            }

            // Custom Attributes
            if (isset($product['custom_attributes'])) {
                $this->line("\n???  CUSTOM ATTRIBUTES (Image related):");
                foreach ($product['custom_attributes'] as $attr) {
                    if (in_array($attr['attribute_code'], ['image', 'small_image', 'thumbnail', 'media_gallery'])) {
                        $this->line("  - {$attr['attribute_code']}: " . ($attr['value'] ?? 'N/A'));
                    }
                }
            }

            // JSON completo
            $this->newLine();
            $this->line("=== JSON COMPLETO ===");
            $this->line(json_encode($product, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    }

    /**
     * Analizar imágenes de un producto
     */
    private function analyzeProductImages($product)
    {
        $info = [
            'has_images' => false,
            'media_gallery_count' => 0,
            'image_attr' => null,
            'small_image_attr' => null,
            'thumbnail_attr' => null,
        ];

        // Verificar media_gallery_entries
        if (isset($product['media_gallery_entries']) && !empty($product['media_gallery_entries'])) {
            $info['media_gallery_count'] = count($product['media_gallery_entries']);
            
            foreach ($product['media_gallery_entries'] as $media) {
                if (($media['media_type'] ?? '') === 'image' && !($media['disabled'] ?? false)) {
                    $info['has_images'] = true;
                    break;
                }
            }
        }

        // Verificar custom_attributes
        if (isset($product['custom_attributes'])) {
            foreach ($product['custom_attributes'] as $attr) {
                $code = $attr['attribute_code'] ?? '';
                $value = $attr['value'] ?? '';

                if ($code === 'image') {
                    $info['image_attr'] = $value;
                    if ($value && $value !== 'no_selection') {
                        $info['has_images'] = true;
                    }
                }

                if ($code === 'small_image') {
                    $info['small_image_attr'] = $value;
                    if ($value && $value !== 'no_selection') {
                        $info['has_images'] = true;
                    }
                }

                if ($code === 'thumbnail') {
                    $info['thumbnail_attr'] = $value;
                    if ($value && $value !== 'no_selection') {
                        $info['has_images'] = true;
                    }
                }
            }
        }

        return $info;
    }

    /**
     * Analizar estructura de imágenes en el lote
     */
    private function analyzeImageStructure($products)
    {
        $withImages = 0;
        $withoutImages = 0;
        $imageFields = [
            'media_gallery_entries' => 0,
            'image_attr' => 0,
            'small_image_attr' => 0,
            'thumbnail_attr' => 0,
        ];

        foreach ($products as $product) {
            $info = $this->analyzeProductImages($product);
            
            if ($info['has_images']) {
                $withImages++;
            } else {
                $withoutImages++;
            }

            if ($info['media_gallery_count'] > 0) $imageFields['media_gallery_entries']++;
            if ($info['image_attr'] && $info['image_attr'] !== 'no_selection') $imageFields['image_attr']++;
            if ($info['small_image_attr'] && $info['small_image_attr'] !== 'no_selection') $imageFields['small_image_attr']++;
            if ($info['thumbnail_attr'] && $info['thumbnail_attr'] !== 'no_selection') $imageFields['thumbnail_attr']++;
        }

        $this->info('=== ANÁLISIS DE IMÁGENES ===');
        $this->line("Con imágenes: {$withImages}");
        $this->line("Sin imágenes: {$withoutImages}");
        $this->newLine();
        $this->line("Campos de imagen encontrados:");
        $this->line("  - media_gallery_entries: {$imageFields['media_gallery_entries']}");
        $this->line("  - image attribute: {$imageFields['image_attr']}");
        $this->line("  - small_image attribute: {$imageFields['small_image_attr']}");
        $this->line("  - thumbnail attribute: {$imageFields['thumbnail_attr']}");
        $this->newLine();
    }
}