<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\API\MagentoApiService;

class DebugProductImages extends Command
{
    protected $signature = 'debug:product-images {--limit=10}';
    protected $description = 'Debug product image detection logic';

    public function handle()
    {
        $magentoApi = app(MagentoApiService::class);
        $limit = (int) $this->option('limit');

        $this->info("=== DEBUG: PRODUCT IMAGE DETECTION ===");
        $this->newLine();

        $response = $magentoApi->getProducts(1, $limit);
        
        if (empty($response['items'])) {
            $this->error('No products found');
            return;
        }

        $withImages = 0;
        $withoutImages = 0;

        foreach ($response['items'] as $product) {
            $hasImages = $this->productHasImages($product);
            
            if ($hasImages) {
                $withImages++;
            } else {
                $withoutImages++;
                $this->line("❌ SIN IMÁGENES: {$product['sku']} - {$product['name']}");
                $this->analyzeProduct($product);
            }
        }

        $this->newLine();
        $this->info("=== RESUMEN ===");
        $this->line("Con imágenes: {$withImages}");
        $this->line("Sin imágenes: {$withoutImages}");
    }

    /**
     * Detectar si producto TIENE imágenes (lógica correcta)
     */
    private function productHasImages($product)
    {
        // 1. Verificar media_gallery_entries
        if (isset($product['media_gallery_entries']) && !empty($product['media_gallery_entries'])) {
            foreach ($product['media_gallery_entries'] as $media) {
                if (($media['media_type'] ?? '') === 'image' && !($media['disabled'] ?? false)) {
                    return true; // TIENE imagen activa
                }
            }
        }

        // 2. Verificar custom_attributes
        if (isset($product['custom_attributes'])) {
            foreach ($product['custom_attributes'] as $attr) {
                $code = $attr['attribute_code'] ?? '';
                $value = $attr['value'] ?? '';
                
                if (in_array($code, ['image', 'small_image', 'thumbnail'])) {
                    if (!empty($value) && $value !== 'no_selection') {
                        return true; // TIENE imagen
                    }
                }
            }
        }

        return false; // NO tiene imágenes
    }

    private function analyzeProduct($product)
    {
        $this->line("  SKU: {$product['sku']}");
        
        // Media gallery
        $mediaCount = count($product['media_gallery_entries'] ?? []);
        $this->line("  media_gallery_entries: {$mediaCount}");
        
        if ($mediaCount > 0) {
            foreach ($product['media_gallery_entries'] as $idx => $media) {
                $type = $media['media_type'] ?? 'N/A';
                $disabled = ($media['disabled'] ?? false) ? 'YES' : 'NO';
                $this->line("    [{$idx}] type={$type}, disabled={$disabled}");
            }
        }

        // Custom attributes
        if (isset($product['custom_attributes'])) {
            $imageAttrs = ['image', 'small_image', 'thumbnail'];
            foreach ($product['custom_attributes'] as $attr) {
                if (in_array($attr['attribute_code'], $imageAttrs)) {
                    $value = $attr['value'] ?? 'null';
                    $this->line("  {$attr['attribute_code']}: {$value}");
                }
            }
        }
        
        $this->newLine();
    }
}
