<?php

namespace App\Http\Controllers;

use App\Models\SalesAnalysisPeriod;
use App\Models\SalesByStore;
use App\Models\SalesByPaymentMethod;
use App\Models\SalesByLocation;
use App\Models\TopProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ExecutiveSalesDashboardController extends Controller
{
    /**
     * Dashboard ejecutivo principal
     */
    public function index(Request $request)
    {
        $year = $request->get('year', Carbon::now()->year);
        $compareYear = $year - 1;
        $selectedMonth = $request->get('month');
        $selectedStore = $request->get('store');

        // Obtener datos del año actual
        $currentYearData = $this->getYearlyData($year, $selectedMonth, $selectedStore);
        
        // Obtener datos del año anterior para comparación
        $previousYearData = $this->getYearlyData($compareYear, $selectedMonth, $selectedStore);

        // Calcular totales y KPIs
        $totals = $this->calculateTotals($currentYearData);
        $comparison = $this->compareYears($currentYearData, $previousYearData);

        // Obtener lista de sucursales
        $stores = DB::table('magento_sources')
            ->where('is_active', true)
            ->orderBy('source_name')
            ->get();

        // Obtener metas (si existen)
        $goals = $this->getGoals($year);

        return view('sales.executive-dashboard', compact(
            'year',
            'compareYear',
            'currentYearData',
            'previousYearData',
            'totals',
            'comparison',
            'stores',
            'selectedMonth',
            'selectedStore',
            'goals'
        ));
    }

    /**
     * Obtener datos anuales agregados por mes
     */
    protected function getYearlyData($year, $month = null, $store = null)
{
    // 1. Inicializar el array con los 12 meses a cero
    $monthlyData = [];
    for ($m = 1; $m <= 12; $m++) {
        $date = Carbon::create($year, $m, 1);
        $monthKey = $date->format('Y-m');
        $monthlyData[$monthKey] = [
            'month' => $date->translatedFormat('F'), // Nombre traducido
            'month_number' => $m,
            'total_revenue' => 0,
            'total_orders' => 0,
            'by_store' => [],
        ];
    }

    // 2. Obtener los periodos existentes
    $query = SalesAnalysisPeriod::where('period_type', 'monthly')
        ->whereYear('period_start', $year)
        ->where('status', 'completed')
        ->with(['salesByStore']);

    if ($month) {
        $query->whereMonth('period_start', $month);
    }

    $periods = $query->get();

    // 3. Llenar los datos reales sobre la estructura inicial
    foreach ($periods as $period) {
        $monthKey = $period->period_start->format('Y-m');
        
        if (isset($monthlyData[$monthKey])) {
            if ($store) {
                $storeData = $period->salesByStore->where('store_code', $store)->first();
                if ($storeData) {
                    $monthlyData[$monthKey]['total_revenue'] = (float)$storeData->total_revenue;
                    $monthlyData[$monthKey]['total_orders'] = (int)$storeData->total_orders;
                }
            } else {
                $monthlyData[$monthKey]['total_revenue'] = (float)$period->total_revenue;
                $monthlyData[$monthKey]['total_orders'] = (int)$period->total_orders;
            }

            foreach ($period->salesByStore as $storeData) {
                $monthlyData[$monthKey]['by_store'][$storeData->store_code] = [
                    'store_name' => $storeData->store_name,
                    'revenue' => (float)$storeData->total_revenue,
                    'orders' => (int)$storeData->total_orders,
                ];
            }
        }
    }

    return $monthlyData;
}

    /**
     * Calcular totales del año
     */
    protected function calculateTotals($yearData)
    {
        $totalRevenue = 0;
        $totalOrders = 0;
        $storeAccumulated = [];

        foreach ($yearData as $monthData) {
            $totalRevenue += $monthData['total_revenue'];
            $totalOrders += $monthData['total_orders'];

            foreach ($monthData['by_store'] as $storeCode => $storeData) {
                if (!isset($storeAccumulated[$storeCode])) {
                    $storeAccumulated[$storeCode] = [
                        'store_name' => $storeData['store_name'],
                        'revenue' => 0,
                        'orders' => 0,
                    ];
                }

                $storeAccumulated[$storeCode]['revenue'] += $storeData['revenue'];
                $storeAccumulated[$storeCode]['orders'] += $storeData['orders'];
            }
        }

        // Ordenar tiendas por revenue
        uasort($storeAccumulated, function($a, $b) {
            return $b['revenue'] <=> $a['revenue'];
        });

        return [
            'total_revenue' => $totalRevenue,
            'total_orders' => $totalOrders,
            'average_order_value' => $totalOrders > 0 ? $totalRevenue / $totalOrders : 0,
            'stores' => $storeAccumulated,
        ];
    }

    /**
     * Comparar dos años
     */
    protected function compareYears($currentYear, $previousYear)
    {
        $currentTotal = array_sum(array_column($currentYear, 'total_revenue'));
        $previousTotal = array_sum(array_column($previousYear, 'total_revenue'));

        $currentOrders = array_sum(array_column($currentYear, 'total_orders'));
        $previousOrders = array_sum(array_column($previousYear, 'total_orders'));

        $revenueGrowth = $previousTotal > 0 
            ? (($currentTotal - $previousTotal) / $previousTotal) * 100 
            : 0;

        $ordersGrowth = $previousOrders > 0 
            ? (($currentOrders - $previousOrders) / $previousOrders) * 100 
            : 0;

        return [
            'revenue_growth' => $revenueGrowth,
            'orders_growth' => $ordersGrowth,
            'revenue_diff' => $currentTotal - $previousTotal,
            'orders_diff' => $currentOrders - $previousOrders,
        ];
    }

    /**
     * Obtener metas del año
     */
    protected function getGoals($year)
    {
        // Aquí puedes implementar una tabla de metas
        // Por ahora retornamos metas ejemplo
        return [
            'monthly_revenue_goal' => 108000,
            'monthly_orders_goal' => 695,
        ];
    }

    /**
     * Datos para gráfico de variación porcentual
     */
    public function variationData(Request $request)
    {
        $year = $request->get('year', Carbon::now()->year);
        $data = $this->getYearlyData($year);

        $variations = [];

        foreach ($data as $monthKey => $monthData) {
            foreach ($monthData['by_store'] as $storeCode => $storeData) {
                if (!isset($variations[$storeCode])) {
                    $variations[$storeCode] = [
                        'store_name' => $storeData['store_name'],
                        'months' => [],
                    ];
                }

                // Calcular variación respecto al mes anterior
                $previousMonth = $this->getPreviousMonthRevenue($data, $monthKey, $storeCode);
                
                $variation = $previousMonth > 0 
                    ? (($storeData['revenue'] - $previousMonth) / $previousMonth) * 100 
                    : 0;

                $variations[$storeCode]['months'][$monthData['month']] = $variation;
            }
        }

        return response()->json($variations);
    }

    /**
     * Obtener revenue del mes anterior
     */
    protected function getPreviousMonthRevenue($data, $currentMonthKey, $storeCode)
    {
        $keys = array_keys($data);
        $currentIndex = array_search($currentMonthKey, $keys);
        
        if ($currentIndex === false || $currentIndex === 0) {
            return 0;
        }

        $previousKey = $keys[$currentIndex - 1];
        
        return $data[$previousKey]['by_store'][$storeCode]['revenue'] ?? 0;
    }

    /**
     * Datos de ubicaciones para mapa
     */
    public function locationMapData(Request $request)
    {
        $year = $request->get('year', Carbon::now()->year);
        
        $locations = SalesByLocation::whereHas('period', function($query) use ($year) {
            $query->whereYear('period_start', $year)
                  ->where('status', 'completed');
        })
        ->select(
            'country',
            'region',
            'city',
            DB::raw('SUM(total_revenue) as total_revenue'),
            DB::raw('SUM(total_orders) as total_orders')
        )
        ->groupBy('country', 'region', 'city')
        ->orderByDesc('total_revenue')
        ->limit(50)
        ->get();

        return response()->json($locations);
    }

    /**
     * Exportar a PDF
     */
    public function exportPdf(Request $request)
    {
        $year = $request->get('year', Carbon::now()->year);
        // Implementar exportación a PDF
        // Por ahora redirigimos al dashboard
        return redirect()->route('sales.executive-dashboard');
    }
}
