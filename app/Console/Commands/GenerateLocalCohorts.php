<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SalesAnalysisPeriod;
use App\Models\CustomerCohort;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class GenerateLocalCohorts extends Command
{
    /**
     * Firma actualizada: el period_id ahora es opcional (?)
     */
    protected $signature = 'sales:generate-cohorts {period_id? : El ID opcional del SalesAnalysisPeriod}';
    protected $description = 'Genera la matriz de cohortes para uno o todos los periodos completados';

    public function handle()
    {
        $periodId = $this->argument('period_id');

        // 1. Determinar qué periodos procesar
        $query = SalesAnalysisPeriod::query();
        
        if ($periodId) {
            $query->where('id', $periodId);
        } else {
            // Si no hay ID, procesamos todos los que estén completados
            $query->where('status', 'completed');
        }

        $periods = $query->get();

        if ($periods->isEmpty()) {
            $this->error("No se encontraron periodos configurados o completados para procesar.");
            return;
        }

        // 2. Obtener todos los pedidos locales una sola vez (Optimización de memoria)
        $this->info("Cargando pedidos locales...");
        $allOrders = DB::table('sales_orders_raw')
            ->select('customer_id', 'grand_total', 'order_created_at')
            ->orderBy('order_created_at', 'asc')
            ->get();

        if ($allOrders->isEmpty()) {
            $this->error("No se encontraron pedidos en la base de datos local 'sales_orders_raw'.");
            return;
        }

        // 3. Pre-calcular la fecha de la primera compra para cada cliente
        $this->info("Definiendo cohortes iniciales por cliente...");
        $customerFirstPurchase = [];
        foreach ($allOrders as $order) {
            if (!isset($customerFirstPurchase[$order->customer_id])) {
                $customerFirstPurchase[$order->customer_id] = Carbon::parse($order->order_created_at)->startOfMonth();
            }
        }

        // 4. Agrupar clientes por su mes de inicio
        $cohortsGroups = [];
        foreach ($customerFirstPurchase as $customerId => $cohortMonth) {
            $monthKey = $cohortMonth->format('Y-m-d');
            $cohortsGroups[$monthKey][] = $customerId;
        }

        // 5. Procesar cada periodo
        foreach ($periods as $period) {
            $this->info("------------------------------------------------------------");
            $this->info("Procesando Periodo ID: {$period->id} | {$period->period_label}");
            
            foreach ($cohortsGroups as $monthKey => $customerIds) {
                $cohortDate = Carbon::parse($monthKey);
                $totalCustomersInCohort = count($customerIds);
                
                // Ingresos iniciales (Mes 0)
                $initialRevenue = DB::table('sales_orders_raw')
                    ->whereIn('customer_id', $customerIds)
                    ->whereBetween('order_created_at', [
                        $cohortDate->copy()->startOfMonth(), 
                        $cohortDate->copy()->endOfMonth()
                    ])
                    ->sum('grand_total');

                $retentionData = [];

                // Calcular meses de retención (Mes 1 al Mes 12)
                for ($i = 1; $i <= 12; $i++) {
                    $targetMonthStart = $cohortDate->copy()->addMonths($i)->startOfMonth();
                    $targetMonthEnd = $cohortDate->copy()->addMonths($i)->endOfMonth();

                    $returningCustomers = DB::table('sales_orders_raw')
                        ->whereIn('customer_id', $customerIds)
                        ->whereBetween('order_created_at', [$targetMonthStart, $targetMonthEnd])
                        ->distinct('customer_id')
                        ->count('customer_id');

                    $retentionData[$i] = ($totalCustomersInCohort > 0) 
                        ? round(($returningCustomers / $totalCustomersInCohort) * 100, 2) 
                        : 0;
                }

                CustomerCohort::updateOrCreate(
                    [
                        'period_id' => $period->id,
                        'cohort_month' => $monthKey,
                    ],
                    [
                        'customers_count' => $totalCustomersInCohort,
                        'initial_revenue' => $initialRevenue,
                        'retention_by_month' => $retentionData,
                    ]
                );

                $this->line("Mes Cohorte {$monthKey} finalizado para Periodo {$period->id}.");
            }
        }

        $this->info("------------------------------------------------------------");
        $this->info("Proceso masivo de cohortes finalizado con éxito.");
    }
}