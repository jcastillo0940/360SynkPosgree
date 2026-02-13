<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AnalyzedProduct;

class CalculateProductNameMatch extends Command
{
    protected $signature = 'analysis:calculate-name-match 
                            {--execution= : ID de la ejecución}
                            {--min-score=60 : Porcentaje mínimo de coincidencia}
                            {--limit= : Limitar cantidad de productos}
                            {--show-details : Mostrar detalles de cada comparación}';

    protected $description = 'Calcular similitud entre nombres de Magento e ICG';

    public function handle()
    {
        $this->info('=== CALCULAR COINCIDENCIA DE NOMBRES ===');
        $this->newLine();

        // Construir query
        $query = AnalyzedProduct::whereNotNull('icg_description');

        if ($executionId = $this->option('execution')) {
            $query->where('analysis_execution_id', $executionId);
            $this->info("Filtrando por ejecución: {$executionId}");
        }

        if ($limit = $this->option('limit')) {
            $query->limit($limit);
            $this->info("Limitando a: {$limit} productos");
        }

        $products = $query->get();

        if ($products->isEmpty()) {
            $this->error('No se encontraron productos con datos de ICG');
            return 1;
        }

        $this->info("Total de productos a analizar: {$products->count()}");
        $this->newLine();

        $minScore = (float) $this->option('min-score');
        $showDetails = $this->option('show-details');

        $bar = $this->output->createProgressBar($products->count());
        $bar->start();

        $results = [];
        $stats = [
            'total' => 0,
            'exact_match' => 0,
            'high_match' => 0,    // >= 80%
            'medium_match' => 0,   // 60-79%
            'low_match' => 0,      // < 60%
            'updated' => 0,
        ];

        foreach ($products as $product) {
            $magentoName = $product->magento_name;
            $icgName = $product->icg_description;

            // Calcular similitud
            $similarity = $this->calculateSimilarity($magentoName, $icgName);

            // Determinar tipo de match
            $matchType = $this->getMatchType($similarity);
            $matchStatus = $similarity >= $minScore ? 'approximate' : 'not_found';

            // Guardar resultado
            $results[] = [
                'sku' => $product->magento_sku,
                'magento_name' => $magentoName,
                'icg_name' => $icgName,
                'similarity' => $similarity,
                'match_type' => $matchType,
            ];

            // Actualizar estadísticas
            $stats['total']++;
            
            if ($similarity >= 95) {
                $stats['exact_match']++;
            } elseif ($similarity >= 80) {
                $stats['high_match']++;
            } elseif ($similarity >= 60) {
                $stats['medium_match']++;
            } else {
                $stats['low_match']++;
            }

            // Actualizar producto en BD
            $product->update([
                'match_score' => $similarity,
                'match_status' => $matchStatus,
                'match_method' => 'name_similarity',
            ]);
            $stats['updated']++;

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Mostrar estadísticas
        $this->info('=== ESTADÍSTICAS ===');
        $this->table(
            ['Rango', 'Cantidad', 'Porcentaje'],
            [
                ['Coincidencia Exacta (≥95%)', $stats['exact_match'], number_format(($stats['exact_match'] / $stats['total']) * 100, 1) . '%'],
                ['Alta Coincidencia (80-94%)', $stats['high_match'], number_format(($stats['high_match'] / $stats['total']) * 100, 1) . '%'],
                ['Media Coincidencia (60-79%)', $stats['medium_match'], number_format(($stats['medium_match'] / $stats['total']) * 100, 1) . '%'],
                ['Baja Coincidencia (<60%)', $stats['low_match'], number_format(($stats['low_match'] / $stats['total']) * 100, 1) . '%'],
            ]
        );

        $this->newLine();
        $this->info("Total procesados: {$stats['total']}");
        $this->info("Total actualizados: {$stats['updated']}");

        // Mostrar ejemplos de cada categoría
        if ($showDetails) {
            $this->newLine(2);
            $this->showExamples($results, 'EXACTAS (≥95%)', 95, 100);
            $this->showExamples($results, 'ALTAS (80-94%)', 80, 94);
            $this->showExamples($results, 'MEDIAS (60-79%)', 60, 79);
            $this->showExamples($results, 'BAJAS (<60%)', 0, 59);
        }

        $this->newLine();
        $this->info('✓ Proceso completado');

        return 0;
    }

    protected function calculateSimilarity($str1, $str2)
{
    if (empty($str1) || empty($str2)) {
        return 0;
    }

    // LIMPIAR: Remover SKUs/códigos numéricos largos del final de ICG
    $str2 = $this->cleanIcgName($str2);

    // Normalizar strings
    $str1 = $this->normalizeString($str1);
    $str2 = $this->normalizeString($str2);

    // Si son idénticos después de normalizar
    if ($str1 === $str2) {
        return 100;
    }

    // Substring match
    if (str_contains($str2, $str1) || str_contains($str1, $str2)) {
        return 85;
    }

    // Similar text
    similar_text($str1, $str2, $percent);

    // Levenshtein (máximo 255 caracteres)
    $levenshtein = levenshtein(substr($str1, 0, 255), substr($str2, 0, 255));
    $maxLength = max(strlen($str1), strlen($str2));
    
    if ($maxLength == 0) {
        return 100;
    }

    $levenshteinPercent = (1 - ($levenshtein / $maxLength)) * 100;

    // Promedio de ambos métodos
    return round(($percent + $levenshteinPercent) / 2, 2);
}

/**
 * Limpiar nombre de ICG removiendo SKU al final
 */
protected function cleanIcgName($name)
{
    // Remover códigos numéricos de 8+ dígitos al final
    // Ejemplos: "PRODUCTO 7501431218031" → "PRODUCTO"
    $name = preg_replace('/\s+\d{8,}$/', '', $name);
    
    // Remover códigos con guiones al final
    // Ejemplos: "PRODUCTO 750-143-121" → "PRODUCTO"
    $name = preg_replace('/\s+[\d\-]{8,}$/', '', $name);
    
    return trim($name);
}

    protected function normalizeString($str)
    {
        $str = strtolower($str);
        $str = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $str);
        $str = preg_replace('/[^a-z0-9\s]/', '', $str);
        $str = preg_replace('/\s+/', ' ', $str);
        return trim($str);
    }

    protected function getMatchType($similarity)
    {
        if ($similarity >= 95) return 'exact';
        if ($similarity >= 80) return 'high';
        if ($similarity >= 60) return 'medium';
        return 'low';
    }

    protected function showExamples($results, $title, $minScore, $maxScore)
    {
        $filtered = array_filter($results, function($r) use ($minScore, $maxScore) {
            return $r['similarity'] >= $minScore && $r['similarity'] <= $maxScore;
        });

        if (empty($filtered)) {
            return;
        }

        // Tomar solo 5 ejemplos
        $examples = array_slice($filtered, 0, 5);

        $this->info("=== EJEMPLOS DE COINCIDENCIAS {$title} ===");
        
        $tableData = [];
        foreach ($examples as $example) {
            $tableData[] = [
                $example['sku'],
                substr($example['magento_name'], 0, 40),
                substr($example['icg_name'], 0, 40),
                number_format($example['similarity'], 1) . '%',
            ];
        }

        $this->table(
            ['SKU', 'Nombre Magento', 'Nombre ICG', 'Similitud'],
            $tableData
        );
        $this->newLine();
    }
}
