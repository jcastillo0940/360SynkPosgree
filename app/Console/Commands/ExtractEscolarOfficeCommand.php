<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\API\IcgApiService;
use Illuminate\Support\Facades\Storage;

class ExtractEscolarOfficeCommand extends Command
{
    // Acepta parámetros min y max para poder ejecutar rangos en paralelo si quieres
    protected $signature = 'icg:extract-escolar {--min=1} {--max=60000}';
    protected $description = 'Extrae artículos recorriendo IDs uno por uno (Fuerza Bruta)';

    public function handle(IcgApiService $icgService)
    {
        $min = (int) $this->option('min');
        $max = (int) $this->option('max');

        $this->info("📡 Iniciando barrido secuencial de IDs: $min al $max");
        $this->info("ℹ️  Filtro: DepartamentoId = 1");

        // Configurar archivo CSV
        $fileName = 'escolar_barrido_' . date('Y-m-d_H-i') . '.csv';
        $filePath = storage_path("app/$fileName");

        // Crear archivo con encabezados iniciales
        // Nota: Los encabezados de almacenes se añadirán dinámicamente si es posible, 
        // pero en modo "stream" es mejor predefinirlos o recolectar todos los BXX conocidos.
        // Aquí usaremos una lista estándar de almacenes para mantener la estructura.
        $knownWarehouses = ['B01', 'B02', 'B03', 'B04', 'B05', 'B06', 'B07', 'B08', 'B09', 'B10', 'B11', 'B12', 'B13', 'M03', 'M04', 'M06', 'M11'];
        $headers = array_merge(['ID', 'SKU', 'Descripción', 'Precio'], $knownWarehouses);

        $file = fopen($filePath, 'w');
        fputs($file, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM
        fputcsv($file, $headers, ';');

        $foundCount = 0;
        $bar = $this->output->createProgressBar($max - $min + 1);
        $bar->start();

        for ($id = $min; $id <= $max; $id++) {

            $result = $icgService->getProductById($id);

            if ($result['success']) {
                $p = $result['data'];

                // 1. Validar DepartamentoId = 1
                if (intval($p['DepartamentoId'] ?? 0) === 1) {

                    // 2. Extraer Precio
                    $precio = 0;
                    if (!empty($p['Precios'])) {
                        $precio = collect($p['Precios'])->firstWhere('TarifaId', 1)['Neto']
                            ?? $p['Precios'][0]['Neto'] ?? 0;
                    }

                    // 3. Preparar Fila
                    $row = [
                        $id,
                        $p['Referencia'] ?? '',
                        $p['Descripcion'] ?? '',
                        $precio
                    ];

                    // 4. Extraer Stock por Almacén
                    $stocks = collect($p['Stocks'] ?? []);
                    foreach ($knownWarehouses as $wh) {
                        $stk = $stocks->firstWhere('AlmacenId', $wh);
                        $row[] = $stk['Disponible'] ?? 0;
                    }

                    // Escribir en el archivo inmediatamente
                    fputcsv($file, $row, ';');
                    $foundCount++;
                }
            }

            // Actualizar barra y limpiar memoria
            $bar->advance();
            if ($id % 100 == 0) {
                flush(); // Forzar escritura a disco
            }

            // Opcional: Pequeña pausa para no tumbar el servidor (0.05s)
            // usleep(50000); 
        }

        $bar->finish();
        fclose($file);

        $this->newLine(2);
        $this->info("✅ Barrido completado.");
        $this->info("📦 Artículos encontrados del Dept 1: $foundCount");
        $this->info("📄 Archivo guardado: $filePath");

        return 0;
    }
}