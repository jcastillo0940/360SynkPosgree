<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\API\MagentoApiService;
use Carbon\Carbon;

class InspectMagentoOrder extends Command
{
    protected $signature = 'magento:inspect-order 
                            {--order-id= : ID específico de orden}
                            {--from= : Fecha desde (Y-m-d)}
                            {--to= : Fecha hasta (Y-m-d)}
                            {--limit=1 : Cantidad de órdenes a mostrar}
                            {--save-json : Guardar resultado en archivo JSON}';

    protected $description = 'Inspeccionar estructura completa de órdenes de Magento';

    protected $magentoApi;

    public function __construct(MagentoApiService $magentoApi)
    {
        parent::__construct();
        $this->magentoApi = $magentoApi;
    }

    public function handle()
    {
        $this->info('=== INSPECCIONAR ÓRDENES DE MAGENTO ===');
        $this->newLine();

        // Si se especifica un ID de orden
        if ($orderId = $this->option('order-id')) {
            $this->inspectSpecificOrder($orderId);
            return 0;
        }

        // Obtener rango de fechas
        $from = $this->option('from') ?: Carbon::now()->subDays(30)->format('Y-m-d');
        $to = $this->option('to') ?: Carbon::now()->format('Y-m-d');
        $limit = (int) $this->option('limit');

        $this->info("Buscando órdenes desde {$from} hasta {$to}");
        $this->info("Límite: {$limit} orden(es)");
        $this->newLine();

        // Obtener órdenes
        $response = $this->magentoApi->getOrders([
            'created_at_from' => $from . ' 00:00:00',
            'created_at_to' => $to . ' 23:59:59',
            'page' => 1,
            'page_size' => $limit,
        ]);

        $orders = $response['items'] ?? [];

        if (empty($orders)) {
            $this->warn('No se encontraron órdenes en el rango especificado');
            return 1;
        }

        $this->info("Total de órdenes encontradas: " . count($orders));
        $this->newLine();

        foreach ($orders as $index => $order) {
            $this->displayOrder($order, $index + 1);
            
            if ($this->option('save-json')) {
                $this->saveToJson($order);
            }
        }

        return 0;
    }

    protected function inspectSpecificOrder($orderId)
    {
        $this->info("Buscando orden ID: {$orderId}");
        $this->newLine();

        try {
            // Magento usa entity_id o increment_id
            $response = $this->magentoApi->getOrders([
                'page' => 1,
                'page_size' => 100,
            ]);

            $orders = $response['items'] ?? [];
            $order = collect($orders)->firstWhere('entity_id', $orderId) 
                  ?? collect($orders)->firstWhere('increment_id', $orderId);

            if (!$order) {
                $this->error("Orden {$orderId} no encontrada");
                return;
            }

            $this->displayOrder($order, 1);

            if ($this->option('save-json')) {
                $this->saveToJson($order);
            }

        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
        }
    }

    protected function displayOrder($order, $number)
    {
        $this->line(str_repeat('=', 80));
        $this->info("ORDEN #{$number}");
        $this->line(str_repeat('=', 80));

        // Información básica
        $this->line('<fg=cyan>INFORMACIÓN BÁSICA</>');
        $this->table(
            ['Campo', 'Valor'],
            [
                ['Entity ID', $order['entity_id'] ?? 'N/A'],
                ['Increment ID', $order['increment_id'] ?? 'N/A'],
                ['Status', $order['status'] ?? 'N/A'],
                ['State', $order['state'] ?? 'N/A'],
                ['Created At', $order['created_at'] ?? 'N/A'],
                ['Grand Total', '$' . number_format($order['grand_total'] ?? 0, 2)],
                ['Subtotal', '$' . number_format($order['subtotal'] ?? 0, 2)],
                ['Tax Amount', '$' . number_format($order['tax_amount'] ?? 0, 2)],
                ['Shipping Amount', '$' . number_format($order['shipping_amount'] ?? 0, 2)],
                ['Discount Amount', '$' . number_format($order['discount_amount'] ?? 0, 2)],
                ['Total Qty Ordered', $order['total_qty_ordered'] ?? 0],
            ]
        );

        // Cliente
        $this->newLine();
        $this->line('<fg=cyan>CLIENTE</>');
        $this->table(
            ['Campo', 'Valor'],
            [
                ['Customer ID', $order['customer_id'] ?? 'N/A'],
                ['Customer Email', $order['customer_email'] ?? 'N/A'],
                ['Customer Firstname', $order['customer_firstname'] ?? 'N/A'],
                ['Customer Lastname', $order['customer_lastname'] ?? 'N/A'],
                ['Customer Group', $order['customer_group_id'] ?? 'N/A'],
            ]
        );

        // Tienda
        $this->newLine();
        $this->line('<fg=cyan>TIENDA</>');
        $this->table(
            ['Campo', 'Valor'],
            [
                ['Store ID', $order['store_id'] ?? 'N/A'],
                ['Store Name', $order['store_name'] ?? 'N/A'],
            ]
        );

        // Source Code (Almacén/Inventario MSI)
        if (isset($order['extension_attributes']['source_code'])) {
            $this->newLine();
            $this->line('<fg=cyan>SOURCE / ALMACÉN (MSI)</>');
            $this->table(
                ['Campo', 'Valor'],
                [
                    ['Source Code', $order['extension_attributes']['source_code']],
                ]
            );
        } else {
            $this->newLine();
            $this->line('<fg=yellow>⚠ No hay source_code en esta orden</>');
        }

        // Payment
        if (isset($order['payment'])) {
            $this->newLine();
            $this->line('<fg=cyan>PAYMENT</>');
            
            $paymentData = [
                ['Method', $order['payment']['method'] ?? 'N/A'],
                ['Amount Ordered', '$' . number_format($order['payment']['amount_ordered'] ?? 0, 2)],
                ['Base Amount Ordered', '$' . number_format($order['payment']['base_amount_ordered'] ?? 0, 2)],
            ];
            
            // Agregar CC Type si existe
            if (isset($order['payment']['cc_type'])) {
                $paymentData[] = ['Card Type', $order['payment']['cc_type']];
            }
            if (isset($order['payment']['cc_last4'])) {
                $paymentData[] = ['Card Last 4', $order['payment']['cc_last4']];
            }
            
            $this->table(['Campo', 'Valor'], $paymentData);
            
            // Mostrar additional_information si no es token JWT
            if (isset($order['payment']['additional_information']) && is_array($order['payment']['additional_information'])) {
                $this->newLine();
                $this->line('<fg=cyan>PAYMENT ADDITIONAL INFO</>');
                
                $additionalInfo = [];
                foreach ($order['payment']['additional_information'] as $key => $value) {
                    if (is_string($value) && strlen($value) < 200) {
                        $additionalInfo[] = [$key, $value];
                    } elseif (is_string($value)) {
                        $additionalInfo[] = [$key, substr($value, 0, 100) . '... (truncado)'];
                    }
                }
                
                if (!empty($additionalInfo)) {
                    $this->table(['Key', 'Value'], $additionalInfo);
                }
            }
        }

        // Shipping Method
        if (isset($order['shipping_description']) || isset($order['shipping_method'])) {
            $this->newLine();
            $this->line('<fg=cyan>SHIPPING METHOD</>');
            $this->table(
                ['Campo', 'Valor'],
                [
                    ['Shipping Description', $order['shipping_description'] ?? 'N/A'],
                    ['Shipping Method', $order['shipping_method'] ?? 'N/A'],
                    ['Shipping Amount', '$' . number_format($order['shipping_amount'] ?? 0, 2)],
                ]
            );
        }

        // Billing Address
        if (isset($order['billing_address'])) {
            $this->newLine();
            $this->line('<fg=cyan>BILLING ADDRESS</>');
            $billing = $order['billing_address'];
            $this->table(
                ['Campo', 'Valor'],
                [
                    ['Country', $billing['country_id'] ?? 'N/A'],
                    ['Region', $billing['region'] ?? 'N/A'],
                    ['City', $billing['city'] ?? 'N/A'],
                    ['Postcode', $billing['postcode'] ?? 'N/A'],
                    ['Street', implode(', ', $billing['street'] ?? [])],
                    ['Telephone', $billing['telephone'] ?? 'N/A'],
                ]
            );
        }

        // Shipping Address
        if (isset($order['extension_attributes']['shipping_assignments'][0]['shipping']['address'])) {
            $this->newLine();
            $this->line('<fg=cyan>SHIPPING ADDRESS</>');
            $shipping = $order['extension_attributes']['shipping_assignments'][0]['shipping']['address'];
            $this->table(
                ['Campo', 'Valor'],
                [
                    ['Country', $shipping['country_id'] ?? 'N/A'],
                    ['Region', $shipping['region'] ?? 'N/A'],
                    ['City', $shipping['city'] ?? 'N/A'],
                    ['Postcode', $shipping['postcode'] ?? 'N/A'],
                    ['Street', implode(', ', $shipping['street'] ?? [])],
                ]
            );

            // Mostrar método de envío
            if (isset($order['extension_attributes']['shipping_assignments'][0]['shipping']['method'])) {
                $shippingMethod = $order['extension_attributes']['shipping_assignments'][0]['shipping']['method'];
                $this->newLine();
                $this->line('<fg=cyan>MÉTODO DE ENVÍO</>');
                $this->line("Método: {$shippingMethod}");
            }

            // Mostrar shipping assignments completo si hay más info
            if (isset($order['extension_attributes']['shipping_assignments'])) {
                $this->newLine();
                $this->line('<fg=cyan>SHIPPING ASSIGNMENTS (COMPLETO)</>');
                $this->line(json_encode($order['extension_attributes']['shipping_assignments'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }
        }

        // Items
        if (isset($order['items']) && !empty($order['items'])) {
            $this->newLine();
            $this->line('<fg=cyan>ITEMS (' . count($order['items']) . ')</>');
            
            $itemsData = [];
            foreach (array_slice($order['items'], 0, 10) as $item) {
                $itemsData[] = [
                    'SKU' => $item['sku'] ?? 'N/A',
                    'Name' => substr($item['name'] ?? 'N/A', 0, 40),
                    'Qty' => $item['qty_ordered'] ?? 0,
                    'Price' => '$' . number_format($item['price'] ?? 0, 2),
                    'Total' => '$' . number_format($item['row_total'] ?? 0, 2),
                ];
            }
            
            $this->table(
                ['SKU', 'Name', 'Qty', 'Price', 'Total'],
                $itemsData
            );
            
            if (count($order['items']) > 10) {
                $this->warn('... y ' . (count($order['items']) - 10) . ' items más');
            }
        }

        // Extension Attributes
        if (isset($order['extension_attributes'])) {
            $this->newLine();
            $this->line('<fg=cyan>EXTENSION ATTRIBUTES (KEYS)</>');
            $this->line(implode(', ', array_keys($order['extension_attributes'])));

            // Mostrar valores importantes de extension_attributes
            $this->newLine();
            $this->line('<fg=cyan>EXTENSION ATTRIBUTES (VALORES CLAVE)</>');
            
            $extData = [];
            
            if (isset($order['extension_attributes']['source_code'])) {
                $extData[] = ['source_code', $order['extension_attributes']['source_code']];
            }
            
            if (isset($order['extension_attributes']['payment_additional_info'])) {
                $paymentInfo = $order['extension_attributes']['payment_additional_info'];
                if (is_array($paymentInfo)) {
                    foreach ($paymentInfo as $key => $value) {
                        if (is_string($value) && strlen($value) < 100) {
                            $extData[] = ["payment_info[{$key}]", $value];
                        }
                    }
                }
            }
            
            if (isset($order['extension_attributes']['applied_taxes'])) {
                $extData[] = ['applied_taxes', json_encode($order['extension_attributes']['applied_taxes'])];
            }

            if (isset($order['extension_attributes']['gift_cards'])) {
                $extData[] = ['gift_cards', json_encode($order['extension_attributes']['gift_cards'])];
            }

            if (!empty($extData)) {
                $this->table(['Key', 'Value'], $extData);
            }
        }

        // Estructura completa (primeras claves)
        $this->newLine();
        $this->line('<fg=cyan>ESTRUCTURA COMPLETA (CLAVES PRINCIPALES)</>');
        $this->line(implode(', ', array_keys($order)));

        $this->newLine(2);
    }

    protected function saveToJson($order)
    {
        $filename = 'order_' . ($order['increment_id'] ?? $order['entity_id']) . '_' . date('Ymd_His') . '.json';
        $filepath = storage_path('app/magento_orders/' . $filename);

        if (!file_exists(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }

        file_put_contents($filepath, json_encode($order, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->info("✓ Orden guardada en: {$filepath}");
    }
}