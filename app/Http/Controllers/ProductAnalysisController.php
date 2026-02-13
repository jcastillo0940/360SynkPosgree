<?php

namespace App\Http\Controllers;

use App\Models\ProductAnalysisExecution;
use App\Models\AnalyzedProduct;
use App\Models\AnalysisConfiguration;
use App\Jobs\ProductAnalysisJob;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ProductAnalysisController extends Controller
{
    /**
     * Mostrar dashboard de análisis
     */
    public function index(Request $request)
    {
        $period = $request->get('period', 'week');
        $dateRange = $this->getDateRange($period);

        // Estadísticas generales
        $stats = [
            'total_executions' => ProductAnalysisExecution::whereBetween('created_at', $dateRange)->count(),
            'running_executions' => ProductAnalysisExecution::running()->count(),
            'total_analyzed' => AnalyzedProduct::whereBetween('analyzed_at', $dateRange)->count(),
            'products_matched' => AnalyzedProduct::whereBetween('analyzed_at', $dateRange)
                ->matched()->count(),
            'products_with_stock' => AnalyzedProduct::whereBetween('analyzed_at', $dateRange)
                ->withStock()->count(),
            'pending_review' => AnalyzedProduct::pending()->count(),
        ];

        // Últimas ejecuciones
        $recentExecutions = ProductAnalysisExecution::with('user')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Productos prioritarios pendientes
        $priorityProducts = AnalyzedProduct::pending()
            ->highPriority()
            ->withStock()
            ->orderBy('match_score', 'desc')
            ->limit(10)
            ->get();

        return view('analysis.index', compact('stats', 'recentExecutions', 'priorityProducts', 'period'));
    }

    /**
     * Mostrar formulario para nuevo análisis
     */
    public function create()
    {
        $configurations = AnalysisConfiguration::visible()
            ->ordered()
            ->get()
            ->groupBy('category');

        return view('analysis.create', compact('configurations'));
    }

    /**
     * Ejecutar análisis
     */
    public function execute(Request $request)
    {
        $validated = $request->validate([
            'analysis_type' => 'required|in:no_images,stock_analysis,full_analysis',
            'filters' => 'nullable|array',
        ]);

        try {
            // Crear ejecución
            $execution = ProductAnalysisExecution::create([
                'job_id' => 'analysis_' . Str::random(10),
                'user_id' => auth()->id(),
                'analysis_type' => $validated['analysis_type'],
                'filters' => $validated['filters'] ?? [],
                'status' => 'pending',
                'configuration_snapshot' => AnalysisConfiguration::getAllAsArray(),
            ]);

            // Despachar job en background
            ProductAnalysisJob::dispatch($execution);

            return redirect()
                ->route('analysis.show', $execution->id)
                ->with('success', 'Análisis iniciado. Job ID: ' . $execution->job_id);

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Error al iniciar análisis: ' . $e->getMessage());
        }
    }

    /**
     * Mostrar detalle de ejecución
     */
    public function show($id)
    {
        $execution = ProductAnalysisExecution::with(['user', 'logs', 'analyzedProducts'])
            ->findOrFail($id);

        // Estadísticas de la ejecución
        $executionStats = [
            'match_rate' => $execution->match_rate,
            'stock_rate' => $execution->stock_rate,
            'pending_count' => $execution->analyzedProducts()->pending()->count(),
            'reviewed_count' => $execution->analyzedProducts()->reviewed()->count(),
            'approved_count' => $execution->analyzedProducts()->approved()->count(),
        ];

        return view('analysis.show', compact('execution', 'executionStats'));
    }

    /**
     * Obtener estado de ejecución (AJAX)
     */
    public function status($id)
    {
        $execution = ProductAnalysisExecution::with(['logs' => function ($query) {
            $query->orderBy('logged_at', 'desc')->limit(50);
        }])->findOrFail($id);

        return response()->json([
            'id' => $execution->id,
            'job_id' => $execution->job_id,
            'status' => $execution->status,
            'status_label' => $execution->status_label,
            'analysis_type' => $execution->analysis_type,
            'analysis_type_label' => $execution->analysis_type_label,
            'started_at' => $execution->started_at?->format('Y-m-d H:i:s'),
            'completed_at' => $execution->completed_at?->format('Y-m-d H:i:s'),
            'duration' => $execution->formatted_duration,
            'progress' => [
                'total_magento_products' => $execution->total_magento_products,
                'products_without_images' => $execution->products_without_images,
                'products_matched' => $execution->products_matched,
                'products_with_stock' => $execution->products_with_stock,
                'products_meeting_criteria' => $execution->products_meeting_criteria,
                'errors_count' => $execution->errors_count,
                'match_rate' => $execution->match_rate,
                'stock_rate' => $execution->stock_rate,
            ],
            'result_message' => $execution->result_message,
            'error_details' => $execution->error_details,
            'export_filename' => $execution->export_filename,
            'logs' => $execution->logs->map(function ($log) {
                return [
                    'id' => $log->id,
                    'level' => $log->level,
                    'message' => $log->formatted_message,
                    'time' => $log->formatted_time,
                    'badge_color' => $log->badge_color,
                    'icon' => $log->icon,
                    'progress_percentage' => $log->progress_percentage,
                ];
            }),
        ]);
    }

    /**
     * Listar productos analizados
     */
    public function products(Request $request)
    {
        $query = AnalyzedProduct::with(['analysisExecution', 'reviewer']);

        // Filtros
        if ($request->filled('execution_id')) {
            $query->where('analysis_execution_id', $request->execution_id);
        }

        if ($request->filled('match_status')) {
            $query->where('match_status', $request->match_status);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('has_stock')) {
            if ($request->has_stock == 'yes') {
                $query->where('meets_stock_criteria', true);
            } else {
                $query->where('meets_stock_criteria', false);
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('magento_sku', 'like', "%{$search}%")
                  ->orWhere('magento_name', 'like', "%{$search}%")
                  ->orWhere('icg_description', 'like', "%{$search}%");
            });
        }

        // Ordenamiento
        $sortBy = $request->get('sort_by', 'analyzed_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $products = $query->paginate(25);

        // Estadísticas
        $stats = [
            'total' => AnalyzedProduct::count(),
            'matched' => AnalyzedProduct::matched()->count(),
            'not_matched' => AnalyzedProduct::notMatched()->count(),
            'with_stock' => AnalyzedProduct::withStock()->count(),
            'pending' => AnalyzedProduct::pending()->count(),
        ];

        return view('analysis.products', compact('products', 'stats'));
    }

    /**
     * Mostrar detalle de producto analizado
     */
    public function productDetail($id)
    {
        $product = AnalyzedProduct::with(['analysisExecution', 'reviewer'])
            ->findOrFail($id);

        return view('analysis.product-detail', compact('product'));
    }

    /**
     * Actualizar estado de producto
     */
    public function updateProductStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,reviewed,approved,rejected',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'notes' => 'nullable|string',
        ]);

        $product = AnalyzedProduct::findOrFail($id);

        $product->markAsReviewed(
            auth()->id(),
            $validated['status'],
            $validated['notes'] ?? null
        );

        if (isset($validated['priority'])) {
            $product->update(['priority' => $validated['priority']]);
        }

        return redirect()
            ->back()
            ->with('success', 'Estado del producto actualizado exitosamente');
    }

    /**
     * Actualizar múltiples productos
     */
    public function bulkUpdate(Request $request)
    {
        $validated = $request->validate([
            'product_ids' => 'required|array',
            'product_ids.*' => 'exists:analyzed_products,id',
            'action' => 'required|in:approve,reject,set_priority',
            'priority' => 'nullable|in:low,medium,high,urgent',
        ]);

        $products = AnalyzedProduct::whereIn('id', $validated['product_ids'])->get();

        foreach ($products as $product) {
            switch ($validated['action']) {
                case 'approve':
                    $product->markAsReviewed(auth()->id(), 'approved');
                    break;

                case 'reject':
                    $product->markAsReviewed(auth()->id(), 'rejected');
                    break;

                case 'set_priority':
                    if (isset($validated['priority'])) {
                        $product->update(['priority' => $validated['priority']]);
                    }
                    break;
            }
        }

        return redirect()
            ->back()
            ->with('success', count($products) . ' productos actualizados exitosamente');
    }

    /**
     * Exportar productos analizados
     */
    public function export(Request $request)
    {
        $query = AnalyzedProduct::query();

        // Aplicar filtros del request
        if ($request->filled('execution_id')) {
            $query->where('analysis_execution_id', $request->execution_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('match_status')) {
            $query->where('match_status', $request->match_status);
        }

        $products = $query->orderBy('match_score', 'desc')->get();

        $filename = 'analyzed_products_' . date('YmdHis') . '.csv';
        $filepath = storage_path('app/exports/' . $filename);

        // Asegurar que el directorio existe
        if (!file_exists(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }

        $file = fopen($filepath, 'w');

        // Headers
        fputcsv($file, [
            'SKU Magento',
            'Nombre Magento',
            'Categoría Magento',
            'Match Status',
            'Match Score',
            'SKU ICG',
            'Descripción ICG',
            'Webname ICG',
            'Departamento',
            'Sección',
            'Familia',
            'Marca',
            'Stock B03',
            'Última Compra B03',
            'Stock B12',
            'Última Compra B12',
            'Stock Total',
            'Cumple Criterios',
            'Precio',
            'Precio Oferta',
            'Prioridad',
            'Estado',
            'Fecha Análisis',
        ]);

        // Datos
        foreach ($products as $product) {
            fputcsv($file, [
                $product->magento_sku,
                $product->magento_name,
                $product->magento_category_name,
                $product->match_status_label,
                $product->match_score,
                $product->icg_barcode,
                $product->icg_description,
                $product->icg_webname,
                $product->icg_departamento,
                $product->icg_seccion,
                $product->icg_familia,
                $product->icg_marca,
                $product->stock_b03,
                $product->last_purchase_b03?->format('Y-m-d'),
                $product->stock_b12,
                $product->last_purchase_b12?->format('Y-m-d'),
                $product->total_stock,
                $product->meets_stock_criteria ? 'Sí' : 'No',
                $product->icg_price,
                $product->icg_offer_price,
                $product->priority_label,
                $product->status_label,
                $product->analyzed_at->format('Y-m-d H:i:s'),
            ]);
        }

        fclose($file);

        return response()->download($filepath, $filename)->deleteFileAfterSend(true);
    }

    /**
     * Descargar reporte de ejecución
     */
    public function downloadReport($id)
    {
        $execution = ProductAnalysisExecution::findOrFail($id);

        if (!$execution->export_path || !file_exists($execution->export_path)) {
            return redirect()
                ->back()
                ->with('error', 'Archivo de reporte no encontrado');
        }

        return response()->download(
            $execution->export_path,
            $execution->export_filename
        );
    }

    /**
     * Configuración de análisis
     */
    public function configuration()
    {
        $configurations = AnalysisConfiguration::visible()
            ->ordered()
            ->get()
            ->groupBy('category');

        return view('analysis.configuration', compact('configurations'));
    }

    /**
     * Actualizar configuración
     */
    public function updateConfiguration(Request $request)
    {
        $validated = $request->validate([
            'configurations' => 'required|array',
        ]);

        foreach ($validated['configurations'] as $key => $value) {
            AnalysisConfiguration::set($key, $value, auth()->id());
        }

        return redirect()
            ->back()
            ->with('success', 'Configuración actualizada exitosamente');
    }

    /**
     * Eliminar ejecución
     */
    public function destroy($id)
    {
        $execution = ProductAnalysisExecution::findOrFail($id);

        if ($execution->status === 'running') {
            return redirect()
                ->back()
                ->with('error', 'No se puede eliminar una ejecución en progreso');
        }

        // Eliminar archivo de reporte si existe
        if ($execution->export_path && file_exists($execution->export_path)) {
            unlink($execution->export_path);
        }

        // Eliminar productos analizados
        $execution->analyzedProducts()->delete();

        // Eliminar ejecución
        $execution->delete();

        return redirect()
            ->route('analysis.index')
            ->with('success', 'Ejecución eliminada exitosamente');
    }

    /**
     * Estadísticas (AJAX)
     */
    public function stats(Request $request)
    {
        $period = $request->get('period', 'week');
        $dateRange = $this->getDateRange($period);

        $stats = [
            'total_executions' => ProductAnalysisExecution::whereBetween('created_at', $dateRange)->count(),
            'running_executions' => ProductAnalysisExecution::running()->count(),
            'completed_executions' => ProductAnalysisExecution::completed()->count(),
            'failed_executions' => ProductAnalysisExecution::failed()->count(),
            'total_analyzed' => AnalyzedProduct::whereBetween('analyzed_at', $dateRange)->count(),
            'products_matched' => AnalyzedProduct::whereBetween('analyzed_at', $dateRange)->matched()->count(),
            'products_with_stock' => AnalyzedProduct::whereBetween('analyzed_at', $dateRange)->withStock()->count(),
            'pending_review' => AnalyzedProduct::pending()->count(),
        ];

        return response()->json($stats);
    }

    /**
     * Obtener rango de fechas según el periodo
     */
    private function getDateRange($period)
    {
        switch ($period) {
            case 'today':
                return [Carbon::today(), Carbon::tomorrow()];
            
            case 'yesterday':
                return [Carbon::yesterday(), Carbon::today()];
            
            case 'week':
                return [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()];
            
            case 'month':
                return [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()];
            
            case 'year':
                return [Carbon::now()->startOfYear(), Carbon::now()->endOfYear()];
            
            default:
                return [Carbon::today(), Carbon::tomorrow()];
        }
    }
}
