<?php

namespace App\Jobs;

use App\Models\SalesAnalysisPeriod;
use App\Models\SalesByStore;
use App\Models\SalesByPaymentMethod;
use App\Models\SalesByLocation;
use App\Models\TopProduct;
use App\Models\CustomerCohort;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ProcessSalesAnalysisJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 14400; // 4 horas
    public $tries = 1;

    protected $period;

    public function __construct(SalesAnalysisPeriod $period)
    {
        $this->period = $period;
    }

    public function handle()
    {
        ini_set('memory_limit', '1024M'); // 1GB
        ini_set('max_execution_time', 14400); // 4 horas
        
        try {
            $this->period->markAsStarted();
            
            Log::info('Iniciando análisis de ventas', [
                'period_id' => $this->period->id,
                'period_type' => $this->period->period_type,
                'date_range' => $this->period->period_start . ' - ' . $this->period->period_end,
            ]);

            // 1. Obtener órdenes de Magento
            $orders = $this->fetchOrders();
            
            if (empty($orders)) {
                $this->period->markAsCompleted();
                Log::info('No hay órdenes en el periodo');
                return;
            }

            Log::info('Órdenes obtenidas', ['count' => count($orders)]);

            // 2. Calcular KPIs generales
            $this->calculateGeneralKPIs($orders);

            // 3. Análisis por sucursal/tienda
            $this->analyzeByStore($orders);

            // 4. Análisis por método de pago
            $this->analyzeByPaymentMethod($orders);

            // 5. Análisis por ubicación
            $this->analyzeByLocation($orders);

            // 6. Top productos
            $this->analyzeTopProducts($orders);

            // 7. Análisis de carritos abandonados
            $this->analyzeAbandonedCarts();

            // 8. Análisis de cohorts de clientes
            $this->analyzeCustomerCohorts($orders);

            $this->period->markAsCompleted();
            
            Log::info('Análisis de ventas completado', [
                'period_id' => $this->period->id,
                'total_revenue' => $this->period->total_revenue,
            ]);

        } catch (\Exception $e) {
            Log::error('Error en análisis de ventas', [
                'period_id' => $this->period->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->period->markAsFailed($e->getMessage());
            throw $e;
        }
    }

    protected function fetchOrders()
    {
        $magentoApi = app(\App\Services\API\MagentoApiService::class);
        
        $orders = [];
        $page = 1;
        $pageSize = 50;
        $maxPages = 50; // Máximo 2,500 órdenes

        Log::info('Iniciando extracción de órdenes', [
            'page_size' => $pageSize,
            'max_pages' => $maxPages
        ]);

        do {
            Log::info("Obteniendo página {$page}");
            
            $response = $magentoApi->getOrders([
                'created_at_from' => $this->period->period_start->format('Y-m-d 00:00:00'),
                'created_at_to' => $this->period->period_end->format('Y-m-d 23:59:59'),
                'page' => $page,
                'page_size' => $pageSize,
            ]);

            $pageOrders = $response['items'] ?? [];
            
            if (empty($pageOrders)) {
                Log::info('No hay más órdenes, terminando extracción');
                break;
            }

            $orders = array_merge($orders, $pageOrders);
            
            Log::info("Página {$page} procesada", [
                'orders_in_page' => count($pageOrders),
                'total_orders' => count($orders)
            ]);
            
            $page++;

            // Pausa entre páginas
            sleep(1);
            
            // Liberar memoria cada 10 páginas
            if ($page % 10 == 0) {
                gc_collect_cycles();
                Log::info('Memoria liberada', ['memory_usage' => memory_get_usage(true) / 1024 / 1024 . ' MB']);
            }

        } while (count($pageOrders) == $pageSize && $page <= $maxPages);

        Log::info('Extracción completada', ['total_orders' => count($orders)]);

        return $orders;
    }

    protected function calculateGeneralKPIs($orders)
    {
        $totalOrders = count($orders);
        $totalRevenue = 0;
        $totalItemsSold = 0;
        $customerEmails = [];
        $returningCustomers = [];

        foreach ($orders as $order) {
            $totalRevenue += $order['grand_total'] ?? 0;
            $totalItemsSold += $order['total_qty_ordered'] ?? 0;
            
            $email = $order['customer_email'] ?? null;
            if ($email) {
                if (isset($customerEmails[$email])) {
                    $returningCustomers[$email] = true;
                }
                $customerEmails[$email] = ($customerEmails[$email] ?? 0) + 1;
            }
        }

        $totalCustomers = count($customerEmails);
        $returningCustomersCount = count($returningCustomers);
        $newCustomersCount = $totalCustomers - $returningCustomersCount;
        $repeatPurchaseRate = $totalCustomers > 0 ? ($returningCustomersCount / $totalCustomers) * 100 : 0;
        $averageOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;

        // Calcular CLV simple
        $clv = $totalCustomers > 0 ? $totalRevenue / $totalCustomers : 0;

        $this->period->update([
            'total_orders' => $totalOrders,
            'total_revenue' => $totalRevenue,
            'average_order_value' => $averageOrderValue,
            'total_items_sold' => $totalItemsSold,
            'total_customers' => $totalCustomers,
            'new_customers' => $newCustomersCount,
            'returning_customers' => $returningCustomersCount,
            'repeat_customers' => $returningCustomersCount,
            'repeat_purchase_rate' => round($repeatPurchaseRate, 2),
            'customer_lifetime_value' => round($clv, 2),
        ]);
    }

    protected function analyzeByStore($orders)
    {
        $storeData = [];

        foreach ($orders as $order) {
            $sourceCode = $order['extension_attributes']['source_code'] ?? 'unknown';
            
            // Buscar nombre de la sucursal
            $source = DB::table('magento_sources')
                ->where('source_code', $sourceCode)
                ->first();
            
            $storeName = $source ? $source->source_name : "Source {$sourceCode}";

            if (!isset($storeData[$sourceCode])) {
                $storeData[$sourceCode] = [
                    'store_code' => $sourceCode,
                    'store_name' => $storeName,
                    'total_orders' => 0,
                    'total_revenue' => 0,
                    'total_items_sold' => 0,
                ];
            }

            $storeData[$sourceCode]['total_orders']++;
            $storeData[$sourceCode]['total_revenue'] += $order['grand_total'] ?? 0;
            $storeData[$sourceCode]['total_items_sold'] += $order['total_qty_ordered'] ?? 0;
        }

        foreach ($storeData as $data) {
            $data['average_order_value'] = $data['total_orders'] > 0 
                ? $data['total_revenue'] / $data['total_orders'] 
                : 0;

            SalesByStore::create(array_merge(['period_id' => $this->period->id], $data));
        }
    }

    protected function analyzeByPaymentMethod($orders)
    {
        $paymentData = [];
        $totalRevenue = array_sum(array_column($orders, 'grand_total'));

        foreach ($orders as $order) {
            $payment = $order['payment'] ?? [];
            $method = $payment['method'] ?? 'unknown';
            
            // Mapear nombres de métodos de pago
            $methodTitle = $this->getPaymentMethodName($method);
            
            if (!isset($paymentData[$method])) {
                $paymentData[$method] = [
                    'payment_method' => substr($method, 0, 100),
                    'payment_method_title' => substr($methodTitle, 0, 100),
                    'total_orders' => 0,
                    'total_revenue' => 0,
                ];
            }

            $paymentData[$method]['total_orders']++;
            $paymentData[$method]['total_revenue'] += $order['grand_total'] ?? 0;
        }

        foreach ($paymentData as $data) {
            $data['percentage'] = $totalRevenue > 0 
                ? ($data['total_revenue'] / $totalRevenue) * 100 
                : 0;

            SalesByPaymentMethod::create(array_merge(['period_id' => $this->period->id], $data));
        }
    }

    protected function getPaymentMethodName($method)
    {
        // Mapeo de métodos personalizados
        if (str_starts_with($method, 'tc_pa_')) {
            return 'Tarjeta en Línea';
        }
        
        if (str_starts_with($method, 'supercarnes_clave')) {
            return 'Tarjeta en Línea (Clave)';
        }
        
        // Mapeo estándar
        $names = [
            'checkmo' => 'Cheque / Giro Postal',
            'cashondelivery' => 'Pago Contra Entrega',
            'banktransfer' => 'Transferencia Bancaria',
            'free' => 'Sin Pago Requerido',
            'purchaseorder' => 'Orden de Compra',
        ];
        
        return $names[$method] ?? ucwords(str_replace(['_', '-'], ' ', $method));
    }

    protected function analyzeByLocation($orders)
    {
        $locationData = [];

        foreach ($orders as $order) {
            $address = $order['billing_address'] ?? $order['shipping_address'] ?? null;
            
            if (!$address) continue;

            $country = $address['country_id'] ?? 'Unknown';
            $region = $address['region'] ?? 'Unknown';
            $city = $address['city'] ?? 'Unknown';

            $key = "{$country}|{$region}|{$city}";

            if (!isset($locationData[$key])) {
                $locationData[$key] = [
                    'country' => $country,
                    'region' => $region,
                    'city' => $city,
                    'postcode' => $address['postcode'] ?? null,
                    'total_orders' => 0,
                    'total_revenue' => 0,
                    'customers' => [],
                ];
            }

            $locationData[$key]['total_orders']++;
            $locationData[$key]['total_revenue'] += $order['grand_total'] ?? 0;
            
            if (!empty($order['customer_email'])) {
                $locationData[$key]['customers'][$order['customer_email']] = true;
            }
        }

        foreach ($locationData as $data) {
            SalesByLocation::create([
                'period_id' => $this->period->id,
                'country' => $data['country'],
                'region' => $data['region'],
                'city' => $data['city'],
                'postcode' => $data['postcode'],
                'total_orders' => $data['total_orders'],
                'total_revenue' => $data['total_revenue'],
                'total_customers' => count($data['customers']),
            ]);
        }
    }

    protected function analyzeTopProducts($orders)
    {
        $productData = [];

        foreach ($orders as $order) {
            $items = $order['items'] ?? [];

            foreach ($items as $item) {
                if (isset($item['parent_item'])) {
                    continue;
                }

                $sku = $item['sku'] ?? 'unknown';
                $name = $item['name'] ?? 'Unknown Product';

                if (!isset($productData[$sku])) {
                    $productData[$sku] = [
                        'sku' => $sku,
                        'product_name' => $name,
                        'quantity_sold' => 0,
                        'total_revenue' => 0,
                        'times_ordered' => 0,
                        'prices' => [],
                    ];
                }

                $qty = $item['qty_ordered'] ?? 0;
                $revenue = $item['row_total'] ?? 0;
                $price = $item['price'] ?? 0;

                $productData[$sku]['quantity_sold'] += $qty;
                $productData[$sku]['total_revenue'] += $revenue;
                $productData[$sku]['times_ordered']++;
                $productData[$sku]['prices'][] = $price;
            }
        }

        usort($productData, function($a, $b) {
            return $b['total_revenue'] <=> $a['total_revenue'];
        });

        $topProducts = array_slice($productData, 0, 100);

        foreach ($topProducts as $data) {
            $avgPrice = count($data['prices']) > 0 
                ? array_sum($data['prices']) / count($data['prices']) 
                : 0;

            TopProduct::create([
                'period_id' => $this->period->id,
                'sku' => $data['sku'],
                'product_name' => $data['product_name'],
                'quantity_sold' => $data['quantity_sold'],
                'total_revenue' => $data['total_revenue'],
                'times_ordered' => $data['times_ordered'],
                'average_price' => $avgPrice,
            ]);
        }
    }

    protected function analyzeAbandonedCarts()
    {
        try {
            $this->period->update([
                'carts_created' => 0,
                'carts_abandoned' => 0,
                'cart_abandonment_rate' => 0,
            ]);

            Log::info('Análisis de carritos abandonados omitido');

        } catch (\Exception $e) {
            Log::warning('Error analizando carritos abandonados', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function analyzeCustomerCohorts($orders)
    {
        $customerFirstOrders = [];

        foreach ($orders as $order) {
            $email = $order['customer_email'] ?? null;
            $orderDate = Carbon::parse($order['created_at']);

            if ($email && !isset($customerFirstOrders[$email])) {
                $customerFirstOrders[$email] = [
                    'first_order_month' => $orderDate->startOfMonth()->format('Y-m-d'),
                    'revenue' => $order['grand_total'] ?? 0,
                ];
            }
        }

        $cohorts = [];
        foreach ($customerFirstOrders as $email => $data) {
            $month = $data['first_order_month'];
            
            if (!isset($cohorts[$month])) {
                $cohorts[$month] = [
                    'customers_count' => 0,
                    'initial_revenue' => 0,
                ];
            }

            $cohorts[$month]['customers_count']++;
            $cohorts[$month]['initial_revenue'] += $data['revenue'];
        }

        foreach ($cohorts as $month => $data) {
            CustomerCohort::create([
                'period_id' => $this->period->id,
                'cohort_month' => $month,
                'customers_count' => $data['customers_count'],
                'initial_revenue' => $data['initial_revenue'],
                'retention_by_month' => [],
            ]);
        }
    }
}