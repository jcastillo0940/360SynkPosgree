<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\API\MagentoApiService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SyncMagentoApiToLocal extends Command
{
    /**
     * El nombre y firma del comando.
     */
    protected $signature = 'magento:sync-api {--days=180}';

    /**
     * La descripción del comando.
     */
    protected $description = 'Sincroniza pedidos desde la API de Magento a la tabla local sales_orders_raw';

    /**
     * Ejecutar el comando de consola.
     */
    public function handle(MagentoApiService $magentoApi)
    {
        $days = $this->option('days');
        $startDate = Carbon::now()->subDays($days)->format('Y-m-d');

        $this->info("Consultando API de Magento desde: $startDate");

        $page = 1;
        $pageSize = 100;
        $totalProcessed = 0;

        // Título visual en consola
        $this->output->title("Iniciando sincronización de pedidos crudos...");

        do {
            $this->line("Procesando página $page...");
            
            // Llamada al servicio con filtros de paginación y fecha
            $response = $magentoApi->getOrders($page, $pageSize, $startDate);
            $orders = $response['items'] ?? [];

            if (empty($orders)) {
                $this->warn("No se encontraron más pedidos en esta página.");
                break;
            }

            foreach ($orders as $order) {
                $magentoOrderId = $order['entity_id'];

                // Datos a insertar o actualizar
                $orderData = [
                    'increment_id'     => $order['increment_id'],
                    'customer_id'      => $order['customer_id'] ?? 0,
                    'grand_total'      => $order['grand_total'],
                    'store_code'       => $order['store_id'] ?? 'default',
                    'order_created_at' => Carbon::parse($order['created_at'])->format('Y-m-d H:i:s'),
                    'updated_at'       => now(),
                ];

                // Lógica manual de "Update or Create" para DB::table
                $exists = DB::table('sales_orders_raw')
                    ->where('magento_order_id', $magentoOrderId)
                    ->exists();

                if ($exists) {
                    DB::table('sales_orders_raw')
                        ->where('magento_order_id', $magentoOrderId)
                        ->update($orderData);
                } else {
                    $orderData['magento_order_id'] = $magentoOrderId;
                    $orderData['created_at'] = now();
                    DB::table('sales_orders_raw')->insert($orderData);
                }

                $totalProcessed++;
            }

            $this->info("Total acumulado: $totalProcessed pedidos...");
            $page++;
            
            // Pausa de 0.5s para respetar los límites de la API de Magento
            usleep(500000); 

        } while (count($orders) >= $pageSize);

        $this->info("Sincronización finalizada exitosamente.");
        $this->info("Se han guardado/actualizado $totalProcessed pedidos en la tabla 'sales_orders_raw'.");
    }
}