@extends('layouts.app')

@section('title', 'Product Analysis')

@section('content')
<div x-data="analysisIndex()" x-init="init()">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Product Analysis Dashboard</h1>
        <p class="text-gray-600 mt-1">Analyze Magento products without images and find matches in ICG</p>
    </div>

    <!-- Filters -->
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center space-x-4">
            <label class="text-sm font-medium text-gray-700">Time period:</label>
            <select x-model="period" @change="loadStats()" 
                    class="border-gray-300 rounded-lg focus:ring-[#0244CD] focus:border-[#0244CD]">
                <option value="today">Today</option>
                <option value="week">This Week</option>
                <option value="month">This Month</option>
                <option value="year">This Year</option>
            </select>
        </div>

        <div class="flex items-center space-x-3">
            <button @click="loadStats()" 
                    class="flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Update
            </button>

            <a href="{{ route('analysis.create') }}" 
               class="flex items-center px-4 py-2 bg-[#0244CD] text-white rounded-lg hover:bg-[#0244CD]/90 transition">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                New Analysis
            </a>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
        <!-- Total Executions -->
        <div class="bg-white border border-gray-200 rounded-xl p-4">
            <div class="flex items-center justify-between mb-2">
                <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
            </div>
            <p class="text-2xl font-bold text-gray-900" x-text="stats.total_executions">{{ $stats['total_executions'] }}</p>
            <p class="text-xs text-gray-600 mt-1">Total Executions</p>
        </div>

        <!-- Running -->
        <div class="bg-gradient-to-br from-blue-50 to-blue-100 border border-blue-200 rounded-xl p-4">
            <div class="flex items-center justify-between mb-2">
                <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
            </div>
            <p class="text-2xl font-bold text-blue-900" x-text="stats.running_executions">{{ $stats['running_executions'] }}</p>
            <p class="text-xs text-blue-700 mt-1">Running Now</p>
        </div>

        <!-- Products Analyzed -->
        <div class="bg-white border border-gray-200 rounded-xl p-4">
            <div class="flex items-center justify-between mb-2">
                <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
            </div>
            <p class="text-2xl font-bold text-gray-900" x-text="stats.total_analyzed.toLocaleString()">{{ number_format($stats['total_analyzed']) }}</p>
            <p class="text-xs text-gray-600 mt-1">Products Analyzed</p>
        </div>

        <!-- Products Matched -->
        <div class="bg-gradient-to-br from-green-50 to-green-100 border border-green-200 rounded-xl p-4">
            <div class="flex items-center justify-between mb-2">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <p class="text-2xl font-bold text-green-900" x-text="stats.products_matched.toLocaleString()">{{ number_format($stats['products_matched']) }}</p>
            <p class="text-xs text-green-700 mt-1">Matched in ICG</p>
        </div>

        <!-- With Stock -->
        <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 border border-yellow-200 rounded-xl p-4">
            <div class="flex items-center justify-between mb-2">
                <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                </svg>
            </div>
            <p class="text-2xl font-bold text-yellow-900" x-text="stats.products_with_stock.toLocaleString()">{{ number_format($stats['products_with_stock']) }}</p>
            <p class="text-xs text-yellow-700 mt-1">With Valid Stock</p>
        </div>

        <!-- Pending Review -->
        <div class="bg-gradient-to-br from-orange-50 to-orange-100 border border-orange-200 rounded-xl p-4">
            <div class="flex items-center justify-between mb-2">
                <svg class="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <p class="text-2xl font-bold text-orange-900" x-text="stats.pending_review.toLocaleString()">{{ $stats['pending_review'] }}</p>
            <p class="text-xs text-orange-700 mt-1">Pending Review</p>
        </div>
    </div>

    <!-- Recent Executions -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-8">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">Recent Analysis Executions</h2>
                    <p class="text-sm text-gray-600 mt-1">Latest product analysis runs</p>
                </div>
                <a href="{{ route('analysis.products') }}" class="text-sm text-[#0244CD] hover:text-[#F6D101]">
                    View all products →
                </a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Job ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Started</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Products</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Matched</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">With Stock</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($recentExecutions as $execution)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ Str::limit($execution->job_id, 15) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                            <span class="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded">
                                {{ $execution->analysis_type_label }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                            {{ $execution->started_at?->format('M d, Y H:i') ?? 'Pending' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($execution->status === 'completed')
                                <span class="px-3 py-1 text-xs font-semibold text-green-800 bg-green-100 rounded-full">Completed</span>
                            @elseif($execution->status === 'running')
                                <span class="px-3 py-1 text-xs font-semibold text-blue-800 bg-blue-100 rounded-full">Running</span>
                            @elseif($execution->status === 'failed')
                                <span class="px-3 py-1 text-xs font-semibold text-red-800 bg-red-100 rounded-full">Failed</span>
                            @else
                                <span class="px-3 py-1 text-xs font-semibold text-gray-800 bg-gray-100 rounded-full">{{ ucfirst($execution->status) }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ number_format($execution->products_without_images) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ number_format($execution->products_matched) }}
                            @if($execution->products_without_images > 0)
                                <span class="text-xs text-gray-500">({{ number_format($execution->match_rate, 1) }}%)</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ number_format($execution->products_with_stock) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm space-x-2">
                            <a href="{{ route('analysis.show', $execution->id) }}" 
                               class="text-[#0244CD] hover:text-[#F6D101]" title="View Details">
                                <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </a>
                            @if($execution->export_filename)
                            <a href="{{ route('analysis.download', $execution->id) }}" 
                               class="text-green-600 hover:text-green-800" title="Download Report">
                                <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                            No analysis executions found. <a href="{{ route('analysis.create') }}" class="text-[#0244CD] hover:underline">Start your first analysis</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Priority Products -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">High Priority Products</h2>
                    <p class="text-sm text-gray-600 mt-1">Products with high match score and valid stock pending review</p>
                </div>
                <a href="{{ route('analysis.products') }}?priority=high&status=pending" class="text-sm text-[#0244CD] hover:text-[#F6D101]">
                    View all →
                </a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Magento SKU</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Match Score</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ICG Product</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($priorityProducts as $product)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ $product->magento_sku }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900">
                            <div class="max-w-xs truncate" title="{{ $product->magento_name }}">
                                {{ $product->magento_name }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <div class="flex items-center">
                                <div class="flex-1 bg-gray-200 rounded-full h-2 w-20 mr-2">
                                    <div class="bg-green-500 h-2 rounded-full" style="width: {{ $product->match_score }}%"></div>
                                </div>
                                <span class="text-gray-900 font-medium">{{ number_format($product->match_score, 1) }}%</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">
                            <div class="max-w-xs truncate" title="{{ $product->icg_webname }}">
                                {{ $product->icg_webname ?? $product->icg_description }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <span class="px-2 py-1 text-xs font-semibold {{ $product->meets_stock_criteria ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }} rounded">
                                {{ $product->total_stock }} units
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <span class="px-2 py-1 text-xs font-semibold bg-{{ $product->priority_color }}-100 text-{{ $product->priority_color }}-800 rounded-full">
                                {{ $product->priority_label }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <a href="{{ route('analysis.product-detail', $product->id) }}" 
                               class="text-[#0244CD] hover:text-[#F6D101]">
                                Review
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                            No high priority products pending review
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
function analysisIndex() {
    return {
        period: '{{ $period }}',
        stats: {
            total_executions: {{ $stats['total_executions'] }},
            running_executions: {{ $stats['running_executions'] }},
            total_analyzed: {{ $stats['total_analyzed'] }},
            products_matched: {{ $stats['products_matched'] }},
            products_with_stock: {{ $stats['products_with_stock'] }},
            pending_review: {{ $stats['pending_review'] }}
        },
        
        init() {
            // Auto-refresh stats every 30 seconds
            setInterval(() => this.loadStats(), 30000);
        },
        
        async loadStats() {
            try {
                const response = await fetch(`{{ route('analysis.stats') }}?period=${this.period}`);
                const data = await response.json();
                this.stats = data;
            } catch (error) {
                console.error('Error loading stats:', error);
            }
        }
    }
}
</script>
@endpush
@endsection
