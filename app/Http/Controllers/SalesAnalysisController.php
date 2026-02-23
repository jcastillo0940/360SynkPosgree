<?php

namespace App\Http\Controllers;

use App\Models\SalesAnalysisPeriod;
use App\Jobs\ProcessSalesAnalysisJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SalesAnalysisController extends Controller
{
    /**
     * Dashboard principal
     */
    public function index(Request $request)
    {
        $periods = SalesAnalysisPeriod::with(['salesByStore', 'salesByPaymentMethod'])
            ->orderBy('period_start', 'desc')
            ->paginate(20);

        return view('sales.index', compact('periods'));
    }

    /**
     * Dashboard de KPIs
     */
    public function dashboard(Request $request)
    {
        $periodType = $request->get('period_type', 'monthly');
        $periodId = $request->get('period_id');

        // Si hay un periodo específico seleccionado
        if ($periodId) {
            $period = SalesAnalysisPeriod::with([
                'salesByStore',
                'salesByPaymentMethod',
                'salesByLocation',
                'topProducts',
                'customerCohorts'
            ])->findOrFail($periodId);
        } else {
            // Obtener el último periodo completado del tipo seleccionado
            $period = SalesAnalysisPeriod::completed()
                ->byPeriodType($periodType)
                ->with([
                    'salesByStore',
                    'salesByPaymentMethod',
                    'salesByLocation',
                    'topProducts',
                    'customerCohorts'
                ])
                ->orderBy('period_start', 'desc')
                ->first();
        }

        // Obtener periodos disponibles para el selector
        $availablePeriods = SalesAnalysisPeriod::completed()
            ->byPeriodType($periodType)
            ->orderBy('period_start', 'desc')
            ->limit(12)
            ->get();

        // Comparación con periodo anterior
        $previousPeriod = null;
        if ($period) {
            $previousPeriod = SalesAnalysisPeriod::completed()
                ->byPeriodType($periodType)
                ->where('period_end', '<', $period->period_start)
                ->orderBy('period_start', 'desc')
                ->first();
        }

        return view('sales.dashboard', compact('period', 'periodType', 'availablePeriods', 'previousPeriod'));
    }

    /**
     * Crear nuevo análisis
     */
    public function create()
    {
        return view('sales.create');
    }

    /**
     * Ejecutar análisis
     */
    public function execute(Request $request)
    {
        $validated = $request->validate([
            'period_type' => 'required|in:daily,weekly,monthly,quarterly,yearly',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        // Verificar que no exista un periodo igual
        $exists = SalesAnalysisPeriod::where('period_type', $validated['period_type'])
            ->where('period_start', $validated['start_date'])
            ->where('period_end', $validated['end_date'])
            ->exists();

        if ($exists) {
            return back()->withErrors(['error' => 'Ya existe un análisis para este periodo.']);
        }

        $period = SalesAnalysisPeriod::create([
            'period_type' => $validated['period_type'],
            'period_start' => $validated['start_date'],
            'period_end' => $validated['end_date'],
            'status' => 'pending',
        ]);

        // Despachar job
        ProcessSalesAnalysisJob::dispatch($period);

        return redirect()->route('sales.show', $period->id)
            ->with('success', 'Análisis de ventas iniciado correctamente.');
    }

    /**
     * Ver detalle de periodo
     */
    public function show($id)
    {
        $period = SalesAnalysisPeriod::with([
            'salesByStore',
            'salesByPaymentMethod',
            'salesByLocation',
            'topProducts',
            'customerCohorts'
        ])->findOrFail($id);

        return view('sales.show', compact('period'));
    }

    /**
     * Obtener estado del análisis (AJAX)
     */
    public function status($id)
    {
        $period = SalesAnalysisPeriod::findOrFail($id);

        return response()->json([
            'status' => $period->status,
            'progress' => $period->status === 'completed' ? 100 : null,
            'total_orders' => $period->total_orders,
            'total_revenue' => $period->total_revenue,
            'error_message' => $period->error_message,
        ]);
    }

    /**
     * Análisis por tienda/sucursal
     */
    public function byStore(Request $request)
    {
        $periodId = $request->get('period_id');

        $query = DB::table('sales_by_store')
            ->select('*')
            ->orderBy('total_revenue', 'desc');

        if ($periodId) {
            $query->where('period_id', $periodId);
        }

        $stores = $query->get();

        $periods = SalesAnalysisPeriod::completed()
            ->orderBy('period_start', 'desc')
            ->limit(12)
            ->get();

        return view('sales.by-store', compact('stores', 'periods', 'periodId'));
    }

    /**
     * Análisis por método de pago
     */
    public function byPaymentMethod(Request $request)
    {
        $periodId = $request->get('period_id');

        $query = DB::table('sales_by_payment_method')
            ->select('*')
            ->orderBy('total_revenue', 'desc');

        if ($periodId) {
            $query->where('period_id', $periodId);
        }

        $payments = $query->get();

        $periods = SalesAnalysisPeriod::completed()
            ->orderBy('period_start', 'desc')
            ->limit(12)
            ->get();

        return view('sales.by-payment', compact('payments', 'periods', 'periodId'));
    }

    /**
     * Análisis por ubicación
     */
    public function byLocation(Request $request)
    {
        $periodId = $request->get('period_id');
        $country = $request->get('country');

        $query = DB::table('sales_by_location')
            ->select('*')
            ->orderBy('total_revenue', 'desc');

        if ($periodId) {
            $query->where('period_id', $periodId);
        }

        if ($country) {
            $query->where('country', $country);
        }

        $locations = $query->limit(100)->get();

        $periods = SalesAnalysisPeriod::completed()
            ->orderBy('period_start', 'desc')
            ->limit(12)
            ->get();

        // Obtener lista de países
        $countries = DB::table('sales_by_location')
            ->select('country')
            ->distinct()
            ->orderBy('country')
            ->pluck('country');

        return view('sales.by-location', compact('locations', 'periods', 'periodId', 'countries', 'country'));
    }

    /**
     * Top productos
     */


    public function topProducts(Request $request)
    {
        // 1. Parámetros de filtrado y orden
        $viewType = $request->get('view_type', 'period');
        $selectedYear = $request->get('year');
        $selectedMonth = $request->get('month');
        $limit = $request->get('limit', 50);
        $sortBy = $request->get('sort_by', 'total_revenue');
        $sortOrder = $request->get('sort_order', 'desc');

        // 2. Consulta con Agrupación Cronológica
        // Usamos selectRaw para que en vistas anuales/totales se sumen los valores
        $query = DB::table('top_products')
            ->join('sales_analysis_periods', 'top_products.period_id', '=', 'sales_analysis_periods.id')
            ->select('top_products.sku', 'top_products.product_name')
            ->selectRaw('SUM(top_products.quantity_sold) as quantity_sold')
            ->selectRaw('SUM(top_products.total_revenue) as total_revenue')
            ->selectRaw('SUM(top_products.times_ordered) as times_ordered')
            ->selectRaw('AVG(top_products.average_price) as average_price')
            ->whereNull('sales_analysis_periods.deleted_at')
            ->groupBy('top_products.sku', 'top_products.product_name');

        // 3. Aplicación de Filtros según la pestaña seleccionada
        if ($viewType === 'period') {
            // Filtro estricto por Año y Mes
            if ($selectedYear) {
                $query->whereYear('sales_analysis_periods.period_start', $selectedYear);
            }
            if ($selectedMonth) {
                $query->whereMonth('sales_analysis_periods.period_start', $selectedMonth);
            }
        } elseif ($viewType === 'year' && $selectedYear) {
            // Suma de todos los meses del año seleccionado
            $query->whereYear('sales_analysis_periods.period_start', $selectedYear);
        }
        // Si es 'total', no filtramos por fecha para sumar todo el histórico

        $products = $query->orderBy($sortBy, $sortOrder)
            ->limit($limit)
            ->get();

        // 4. Datos para los selectores de la interfaz
        $years = SalesAnalysisPeriod::selectRaw(DB::getDriverName() === 'pgsql' ? 'EXTRACT(YEAR FROM period_start) as year' : 'YEAR(period_start) as year')
            ->distinct()->orderBy('year', 'desc')->pluck('year');

        $months = [];
        if ($selectedYear) {
            $months = SalesAnalysisPeriod::whereYear('period_start', $selectedYear)
                ->selectRaw(DB::getDriverName() === 'pgsql' ? 'EXTRACT(MONTH FROM period_start) as month_num' : 'MONTH(period_start) as month_num')
                ->distinct()->orderBy('month_num', 'asc')->get()
                ->map(fn($item) => [
                    'num' => $item->month_num,
                    'name' => \Carbon\Carbon::create()->month($item->month_num)->translatedFormat('F')
                ]);
        }

        return view('sales.top-products', compact(
            'products',
            'years',
            'months',
            'selectedYear',
            'selectedMonth',
            'limit',
            'viewType',
            'sortBy',
            'sortOrder'
        ));
    }


    /**
     * Análisis de cohorts
     */
    public function cohorts(Request $request)
    {
        $periodId = $request->get('period_id');

        $query = DB::table('customer_cohorts')
            ->select('*')
            ->orderBy('cohort_month', 'desc');

        if ($periodId) {
            $query->where('period_id', $periodId);
        }

        $cohorts = $query->get();

        $periods = SalesAnalysisPeriod::completed()
            ->orderBy('period_start', 'desc')
            ->limit(12)
            ->get();

        return view('sales.cohorts', compact('cohorts', 'periods', 'periodId'));
    }

    /**
     * Comparación de periodos
     */
    public function compare(Request $request)
    {
        $period1Id = $request->get('period1');
        $period2Id = $request->get('period2');

        $period1 = null;
        $period2 = null;

        if ($period1Id) {
            $period1 = SalesAnalysisPeriod::with(['salesByStore', 'salesByPaymentMethod'])->find($period1Id);
        }

        if ($period2Id) {
            $period2 = SalesAnalysisPeriod::with(['salesByStore', 'salesByPaymentMethod'])->find($period2Id);
        }

        $periods = SalesAnalysisPeriod::completed()
            ->orderBy('period_start', 'desc')
            ->get();

        return view('sales.compare', compact('period1', 'period2', 'periods', 'period1Id', 'period2Id'));
    }

    /**
     * Exportar a CSV
     */
    public function export($id)
    {
        $period = SalesAnalysisPeriod::with([
            'salesByStore',
            'salesByPaymentMethod',
            'topProducts'
        ])->findOrFail($id);

        $filename = "sales_analysis_{$period->id}_" . date('Ymd') . ".csv";
        $filepath = storage_path("app/exports/{$filename}");

        if (!file_exists(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }

        $file = fopen($filepath, 'w');

        // BOM para UTF-8
        fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // KPIs Generales
        fputcsv($file, ['=== KPIs GENERALES ===']);
        fputcsv($file, ['Periodo', $period->period_label]);
        fputcsv($file, ['Desde', $period->period_start->format('Y-m-d')]);
        fputcsv($file, ['Hasta', $period->period_end->format('Y-m-d')]);
        fputcsv($file, []);
        fputcsv($file, ['Total Órdenes', $period->total_orders]);
        fputcsv($file, ['Revenue Total', '$' . number_format($period->total_revenue, 2)]);
        fputcsv($file, ['AOV', '$' . number_format($period->average_order_value, 2)]);
        fputcsv($file, ['Total Clientes', $period->total_customers]);
        fputcsv($file, ['Clientes Nuevos', $period->new_customers]);
        fputcsv($file, ['Clientes Recurrentes', $period->returning_customers]);
        fputcsv($file, ['Tasa Compra Repetida', $period->repeat_purchase_rate . '%']);
        fputcsv($file, ['CLV', '$' . number_format($period->customer_lifetime_value, 2)]);
        fputcsv($file, []);

        // Ventas por Tienda
        fputcsv($file, ['=== VENTAS POR TIENDA ===']);
        fputcsv($file, ['Tienda', 'Órdenes', 'Revenue', 'AOV']);
        foreach ($period->salesByStore as $store) {
            fputcsv($file, [
                $store->store_name,
                $store->total_orders,
                '$' . number_format($store->total_revenue, 2),
                '$' . number_format($store->average_order_value, 2),
            ]);
        }
        fputcsv($file, []);

        // Métodos de Pago
        fputcsv($file, ['=== MÉTODOS DE PAGO ===']);
        fputcsv($file, ['Método', 'Órdenes', 'Revenue', 'Porcentaje']);
        foreach ($period->salesByPaymentMethod as $payment) {
            fputcsv($file, [
                $payment->payment_method_title,
                $payment->total_orders,
                '$' . number_format($payment->total_revenue, 2),
                number_format($payment->percentage, 2) . '%',
            ]);
        }
        fputcsv($file, []);

        // Top Productos
        fputcsv($file, ['=== TOP 50 PRODUCTOS ===']);
        fputcsv($file, ['SKU', 'Producto', 'Cantidad', 'Revenue', 'Veces Ordenado']);
        foreach ($period->topProducts->take(50) as $product) {
            fputcsv($file, [
                $product->sku,
                $product->product_name,
                $product->quantity_sold,
                '$' . number_format($product->total_revenue, 2),
                $product->times_ordered,
            ]);
        }

        fclose($file);

        return response()->download($filepath)->deleteFileAfterSend();
    }

    /**
     * Eliminar periodo
     */
    public function destroy($id)
    {
        $period = SalesAnalysisPeriod::findOrFail($id);
        $period->delete();

        return redirect()->route('sales.index')
            ->with('success', 'Periodo eliminado correctamente.');
    }

    /**
     * API: Datos para gráficos
     */
    public function chartData(Request $request)
    {
        $periodId = $request->get('period_id');
        $chartType = $request->get('type', 'revenue');

        $period = SalesAnalysisPeriod::with([
            'salesByStore',
            'salesByPaymentMethod',
            'topProducts'
        ])->findOrFail($periodId);

        $data = [];

        switch ($chartType) {
            case 'revenue_by_store':
                $data = $period->salesByStore->map(function ($store) {
                    return [
                        'label' => $store->store_name,
                        'value' => (float) $store->total_revenue,
                    ];
                });
                break;

            case 'orders_by_payment':
                $data = $period->salesByPaymentMethod->map(function ($payment) {
                    return [
                        'label' => $payment->payment_method_title,
                        'value' => $payment->total_orders,
                    ];
                });
                break;

            case 'top_products':
                $data = $period->topProducts->take(10)->map(function ($product) {
                    return [
                        'label' => substr($product->product_name, 0, 30),
                        'value' => (float) $product->total_revenue,
                    ];
                });
                break;
        }

        return response()->json($data);
    }
}
