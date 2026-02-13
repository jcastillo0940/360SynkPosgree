<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\WorkflowController;
use App\Http\Controllers\ExecutionController;
use App\Http\Controllers\ScheduleRuleController;
use App\Http\Controllers\CategoryMappingController;
use App\Http\Controllers\ConfigurationController;
use App\Http\Controllers\ProductAnalysisController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SalesAnalysisController;
   use App\Http\Controllers\ExecutiveSalesDashboardController;

   Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/stats', [DashboardController::class, 'stats'])->name('dashboard.stats');
    Route::get('/dashboard/recent-executions', [DashboardController::class, 'recentExecutions'])->name('dashboard.recent');

    Route::prefix('workflows')->name('workflows.')->group(function () {
        Route::get('/', [WorkflowController::class, 'index'])->name('index');
        Route::get('/create', [WorkflowController::class, 'create'])->name('create');
        Route::post('/store', [WorkflowController::class, 'store'])->name('store');
        Route::get('/{id}', [WorkflowController::class, 'show'])->name('show');
        Route::get('/{id}/execute', [WorkflowController::class, 'execute'])->name('execute');
        Route::post('/{id}/execute', [WorkflowController::class, 'processExecution'])->name('process');
        Route::post('/{id}/toggle', [WorkflowController::class, 'toggle'])->name('toggle');
        
        Route::get('/{id}/info', [WorkflowController::class, 'getInfo'])->name('info');
        Route::get('/{id}/stats', [WorkflowController::class, 'stats'])->name('stats');
    });

    Route::prefix('executions')->name('executions.')->group(function () {
        Route::get('/', [ExecutionController::class, 'index'])->name('index');
        Route::get('/{id}', [ExecutionController::class, 'show'])->name('show');
        Route::delete('/{id}', [ExecutionController::class, 'destroy'])->name('destroy');
        
        Route::post('/{id}/cancel', [ExecutionController::class, 'cancel'])->name('cancel');
        Route::post('/{id}/retry', [ExecutionController::class, 'retry'])->name('retry');
        Route::get('/{id}/download-csv', [ExecutionController::class, 'downloadCsv'])->name('download-csv');
        
        Route::get('/{id}/status', [ExecutionController::class, 'status'])->name('status');
        Route::get('/{id}/logs', [ExecutionController::class, 'logs'])->name('logs');
    });

    Route::prefix('schedule')->name('schedule.')->group(function () {
        Route::get('/', [ScheduleRuleController::class, 'index'])->name('index');
        Route::get('/timeline', [ScheduleRuleController::class, 'timeline'])->name('timeline');
        
        Route::get('/list', [ScheduleRuleController::class, 'list'])->name('list');
        Route::get('/create', [ScheduleRuleController::class, 'create'])->name('create');
        Route::post('/store', [ScheduleRuleController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [ScheduleRuleController::class, 'edit'])->name('edit');
        Route::put('/{id}', [ScheduleRuleController::class, 'update'])->name('update');
        Route::delete('/{id}', [ScheduleRuleController::class, 'destroy'])->name('destroy');
        
        Route::post('/{id}/toggle', [ScheduleRuleController::class, 'toggle'])->name('toggle');
        Route::post('/{id}/run-now', [ScheduleRuleController::class, 'runNow'])->name('run-now');
        
        Route::get('/{id}/info', [ScheduleRuleController::class, 'getInfo'])->name('info');
    });

    Route::prefix('categories')->name('categories.')->group(function () {
        Route::get('/', [CategoryMappingController::class, 'index'])->name('index');
        Route::get('/create', [CategoryMappingController::class, 'create'])->name('create');
        Route::post('/store', [CategoryMappingController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [CategoryMappingController::class, 'edit'])->name('edit');
        Route::put('/{id}', [CategoryMappingController::class, 'update'])->name('update');
        Route::delete('/{id}', [CategoryMappingController::class, 'destroy'])->name('destroy');
        
        Route::post('/{id}/toggle', [CategoryMappingController::class, 'toggle'])->name('toggle');
        Route::post('/sync', [CategoryMappingController::class, 'sync'])->name('sync');
        Route::post('/import', [CategoryMappingController::class, 'import'])->name('import');
        Route::get('/export', [CategoryMappingController::class, 'export'])->name('export');
        Route::post('/sync-counts', [CategoryMappingController::class, 'syncCounts'])->name('sync-counts');
        
        Route::get('/get-icg-categories', [CategoryMappingController::class, 'getIcgCategories'])->name('get-icg');
        Route::get('/get-magento-categories', [CategoryMappingController::class, 'getMagentoCategories'])->name('get-magento');
    });

// ============================================
// RUTAS DE ANÃLISIS DE PRODUCTOS
// ============================================
Route::prefix('analysis')->name('analysis.')->group(function () {
    // Dashboard y lista
    Route::get('/', [ProductAnalysisController::class, 'index'])->name('index');
    Route::get('/stats', [ProductAnalysisController::class, 'stats'])->name('stats');
    
    // Crear y ejecutar anÃ¡lisis
    Route::get('/create', [ProductAnalysisController::class, 'create'])->name('create');
    Route::post('/execute', [ProductAnalysisController::class, 'execute'])->name('execute');
    
    // Detalle de ejecuciÃ³n
    Route::get('/{id}', [ProductAnalysisController::class, 'show'])->name('show');
    Route::get('/{id}/status', [ProductAnalysisController::class, 'status'])->name('status');
    Route::delete('/{id}', [ProductAnalysisController::class, 'destroy'])->name('destroy');
    Route::get('/{id}/download', [ProductAnalysisController::class, 'downloadReport'])->name('download');
    
    // Productos analizados - ORDEN IMPORTANTE
    Route::get('/products/list', [ProductAnalysisController::class, 'products'])->name('products');
    Route::get('/products/export', [ProductAnalysisController::class, 'export'])->name('export'); // ðŸ‘ˆ ANTES de {id}
    Route::post('/products/bulk-update', [ProductAnalysisController::class, 'bulkUpdate'])->name('bulk-update'); // ðŸ‘ˆ ANTES de {id}
    Route::get('/products/{id}', [ProductAnalysisController::class, 'productDetail'])->name('product-detail'); // ðŸ‘ˆ AL FINAL
    Route::post('/products/{id}/update-status', [ProductAnalysisController::class, 'updateProductStatus'])->name('update-product-status');
    
    // ConfiguraciÃ³n
    Route::get('/config/settings', [ProductAnalysisController::class, 'configuration'])->name('configuration');
    Route::post('/config/update', [ProductAnalysisController::class, 'updateConfiguration'])->name('update-configuration');
});

    Route::prefix('configuration')->name('configuration.')->group(function () {
        Route::get('/', [ConfigurationController::class, 'index'])->name('index');
        Route::post('/update', [ConfigurationController::class, 'update'])->name('update');
        Route::post('/reset', [ConfigurationController::class, 'reset'])->name('reset');
        Route::get('/export', [ConfigurationController::class, 'export'])->name('export');
        
        Route::match(['get', 'post'], '/test-icg-api', [ConfigurationController::class, 'testIcgApi'])->name('test-icg');
        Route::match(['get', 'post'], '/test-magento-api', [ConfigurationController::class, 'testMagentoApi'])->name('test-magento');
        Route::match(['get', 'post'], '/test-ftp', [ConfigurationController::class, 'testFtp'])->name('test-ftp');
        
        Route::get('/value/{key}', [ConfigurationController::class, 'getValue'])->name('value');
    });

    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'edit'])->name('edit');
        Route::patch('/', [ProfileController::class, 'update'])->name('update');
        Route::delete('/', [ProfileController::class, 'destroy'])->name('destroy');
    });
});
   
// ============================================
// RUTAS DE ANÁLISIS DE VENTAS
// ============================================
Route::prefix('sales')->name('sales.')->middleware(['auth', 'verified'])->group(function () {
    // Dashboard Ejecutivo (Principal)
    Route::get('/executive', [ExecutiveSalesDashboardController::class, 'index'])->name('executive-dashboard');
    Route::get('/executive/variation-data', [ExecutiveSalesDashboardController::class, 'variationData'])->name('executive-dashboard.variation');
    Route::get('/executive/location-map', [ExecutiveSalesDashboardController::class, 'locationMapData'])->name('executive-dashboard.location-map');
    Route::get('/executive/pdf', [ExecutiveSalesDashboardController::class, 'exportPdf'])->name('executive-dashboard.pdf');
    
    // Dashboard técnico
    Route::get('/', [SalesAnalysisController::class, 'index'])->name('index');
    Route::get('/dashboard', [SalesAnalysisController::class, 'dashboard'])->name('dashboard');
    
    // Crear y ejecutar análisis
    Route::get('/create', [SalesAnalysisController::class, 'create'])->name('create');
    Route::post('/execute', [SalesAnalysisController::class, 'execute'])->name('execute');
    
    // Detalle de periodo
    Route::get('/{id}', [SalesAnalysisController::class, 'show'])->name('show');
    Route::get('/{id}/status', [SalesAnalysisController::class, 'status'])->name('status');
    Route::delete('/{id}', [SalesAnalysisController::class, 'destroy'])->name('destroy');
    Route::get('/{id}/export', [SalesAnalysisController::class, 'export'])->name('export');
    
    // Análisis específicos
    Route::get('/reports/by-store', [SalesAnalysisController::class, 'byStore'])->name('by-store');
    Route::get('/reports/by-payment', [SalesAnalysisController::class, 'byPaymentMethod'])->name('by-payment');
    Route::get('/reports/by-location', [SalesAnalysisController::class, 'byLocation'])->name('by-location');
    Route::get('/reports/top-products', [SalesAnalysisController::class, 'topProducts'])->name('top-products');
    Route::get('/reports/cohorts', [SalesAnalysisController::class, 'cohorts'])->name('cohorts');
    Route::get('/reports/compare', [SalesAnalysisController::class, 'compare'])->name('compare');
    
    // API para gráficos
    Route::get('/api/chart-data', [SalesAnalysisController::class, 'chartData'])->name('chart-data');
});
   
   

require __DIR__.'/auth.php';