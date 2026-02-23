<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\API\IcgApiService;

class GetIcgProductsCommand extends Command
{
    /**
     * El nombre y firma del comando de consola.
     *
     * @var string
     */
    protected $signature = 'icg:get-products {skus : Lista de SKUs separados por coma y sin espacios}';

    /**
     * La descripción del comando.
     *
     * @var string
     */
    protected $description = 'Obtiene productos desde ICG y genera un CSV con los resultados (encontrados y errores), conservando ceros a la izquierda.';

    /**
     * Ejecuta el comando de la consola.
     */
    public function handle(IcgApiService $icgService)
    {
        // Obtener el argumento de la consola
        $skusInput = $this->argument('skus');

        // Convertir el string en un array y limpiarlo
        $skusArray = explode(',', $skusInput);
        $skusArray = array_map('trim', $skusArray);
        $skusArray = array_filter($skusArray);

        if (empty($skusArray)) {
            $this->error('No se proporcionaron SKUs válidos.');
            return Command::FAILURE;
        }

        $this->info("Consultando " . count($skusArray) . " SKU(s) en la API de ICG...");

        // Llamar al servicio
        $result = $icgService->getProductsBySkus($skusArray);

        // Generar nombre de archivo con fecha y hora para no sobreescribir
        $fileName = 'icg_resultados_' . now()->format('Y_m_d_His') . '.csv';
        $filePath = storage_path('app/' . $fileName);

        // Abrir archivo en modo escritura
        $file = fopen($filePath, 'w');

        // Agregar BOM (Byte Order Mark) para que Excel lea correctamente los tildes y caracteres UTF-8
        fputs($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Escribir los encabezados del CSV
        fputcsv($file, ['SKU', 'Nombre', 'Precio Regular', 'Precio Oferta', 'Estado']);

        // 1. Escribir los productos encontrados
        if (!empty($result['data'])) {
            foreach ($result['data'] as $product) {
                // Utiliza ARTEAN (CodigoBarras1) primero, si está vacío toma ARTCOD (Referencia)
                $rawSku = $product['ARTEAN'] ?? $product['ARTCOD'] ?? '';
                
                // Formato ="SKU" para obligar a Excel a mantener los ceros a la izquierda
                $safeSku = '="' . $rawSku . '"';

                fputcsv($file, [
                    $safeSku,
                    $product['ARTDES'] ?? 'N/A',
                    $product['PVPTARIF'] ?? '0.00',
                    $product['PVPOFER'] ?? 'Sin oferta',
                    'Encontrado'
                ]);
            }
        }

        // 2. Escribir los SKUs que dieron error o no se encontraron
        if (!empty($result['errors'])) {
            foreach ($result['errors'] as $sku => $error) {
                // Formato ="SKU" para conservar los ceros en los errores también
                $safeSku = '="' . $sku . '"';

                fputcsv($file, [
                    $safeSku,
                    'N/A', // Sin nombre
                    'N/A', // Sin precio
                    'N/A', // Sin oferta
                    'Error: ' . $error
                ]);
            }
        }

        // Cerrar el archivo
        fclose($file);

        // Mostrar mensaje de éxito en la consola
        $this->info("\n¡Proceso completado!");
        $this->line("Se encontraron: <fg=green>{$result['total_found']}</> productos.");
        $this->line("Fallaron: <fg=red>" . count($result['errors']) . "</> SKUs.");
        $this->info("El archivo CSV ha sido guardado en: {$filePath}");

        return Command::SUCCESS;
    }
}