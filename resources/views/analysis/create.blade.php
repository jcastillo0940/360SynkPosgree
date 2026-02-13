@extends('layouts.app')

@section('title', 'New Product Analysis')

@section('content')
<div x-data="newAnalysis()">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">New Product Analysis</h1>
                <p class="text-gray-600 mt-1">Configure and start a new analysis of products without images</p>
            </div>
            <a href="{{ route('analysis.index') }}" class="text-[#0244CD] hover:text-[#F6D101] flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Dashboard
            </a>
        </div>
    </div>

    <form action="{{ route('analysis.execute') }}" method="POST" class="max-w-4xl">
        @csrf

        <!-- Analysis Type Selection -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Analysis Type</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- No Images -->
                <label class="relative cursor-pointer">
                    <input type="radio" name="analysis_type" value="no_images" x-model="analysisType" class="sr-only peer" required>
                    <div class="border-2 border-gray-300 rounded-lg p-6 peer-checked:border-[#0244CD] peer-checked:bg-blue-50 hover:border-gray-400 transition">
                        <div class="flex flex-col items-center text-center">
                            <svg class="w-12 h-12 text-gray-600 peer-checked:text-[#0244CD] mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <h3 class="font-semibold text-gray-900 mb-1">Products Without Images</h3>
                            <p class="text-xs text-gray-600">Analyze only Magento products that don't have images</p>
                        </div>
                    </div>
                </label>

                <!-- Stock Analysis -->
                <label class="relative cursor-pointer">
                    <input type="radio" name="analysis_type" value="stock_analysis" x-model="analysisType" class="sr-only peer">
                    <div class="border-2 border-gray-300 rounded-lg p-6 peer-checked:border-[#0244CD] peer-checked:bg-blue-50 hover:border-gray-400 transition">
                        <div class="flex flex-col items-center text-center">
                            <svg class="w-12 h-12 text-gray-600 peer-checked:text-[#0244CD] mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                            <h3 class="font-semibold text-gray-900 mb-1">Stock Analysis</h3>
                            <p class="text-xs text-gray-600">Focus on products with valid stock in B03/B12</p>
                        </div>
                    </div>
                </label>

                <!-- Full Analysis -->
                <label class="relative cursor-pointer">
                    <input type="radio" name="analysis_type" value="full_analysis" x-model="analysisType" class="sr-only peer">
                    <div class="border-2 border-gray-300 rounded-lg p-6 peer-checked:border-[#0244CD] peer-checked:bg-blue-50 hover:border-gray-400 transition">
                        <div class="flex flex-col items-center text-center">
                            <svg class="w-12 h-12 text-gray-600 peer-checked:text-[#0244CD] mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                            </svg>
                            <h3 class="font-semibold text-gray-900 mb-1">Full Analysis</h3>
                            <p class="text-xs text-gray-600">Complete analysis with all available filters</p>
                        </div>
                    </div>
                </label>
            </div>

            @error('analysis_type')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Current Configuration -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold text-gray-900">Current Configuration</h2>
                <a href="{{ route('analysis.configuration') }}" class="text-sm text-[#0244CD] hover:text-[#F6D101]">
                    Edit Configuration →
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($configurations as $category => $configs)
                    @foreach($configs->take(3) as $config)
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <p class="text-xs text-gray-500 uppercase tracking-wider">{{ $config->label }}</p>
                                <p class="text-lg font-semibold text-gray-900 mt-1">{{ $config->display_value }}</p>
                            </div>
                            @if($config->type === 'boolean')
                                @if($config->parsed_value)
                                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                @else
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                @endif
                            @endif
                        </div>
                    </div>
                    @endforeach
                @endforeach
            </div>
        </div>

        <!-- Advanced Filters (Optional) -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6" x-show="showAdvanced" x-collapse>
            <h2 class="text-xl font-bold text-gray-900 mb-4">Advanced Filters</h2>
            
            <div class="space-y-4">
                <!-- Category Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Filter by Category</label>
                    <select name="filters[category_id]" class="w-full border-gray-300 rounded-lg focus:ring-[#0244CD] focus:border-[#0244CD]">
                        <option value="">All Categories</option>
                        <!-- Categories will be loaded dynamically -->
                    </select>
                </div>

                <!-- SKU Range -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">SKU Starts With</label>
                        <input type="text" name="filters[sku_prefix]" 
                               class="w-full border-gray-300 rounded-lg focus:ring-[#0244CD] focus:border-[#0244CD]"
                               placeholder="e.g., PRD">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Limit Products</label>
                        <input type="number" name="filters[limit]" 
                               class="w-full border-gray-300 rounded-lg focus:ring-[#0244CD] focus:border-[#0244CD]"
                               placeholder="e.g., 100">
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-between">
            <button type="button" @click="showAdvanced = !showAdvanced" 
                    class="text-sm text-gray-600 hover:text-gray-900 flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                </svg>
                <span x-text="showAdvanced ? 'Hide' : 'Show'"></span> Advanced Filters
            </button>

            <div class="flex items-center space-x-3">
                <a href="{{ route('analysis.index') }}" 
                   class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                    Cancel
                </a>
                <button type="submit" 
                        class="px-6 py-3 bg-[#0244CD] text-white rounded-lg hover:bg-[#0244CD]/90 transition flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    Start Analysis
                </button>
            </div>
        </div>
    </form>

    <!-- Info Cards -->
    <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
            <div class="flex items-start">
                <svg class="w-6 h-6 text-blue-600 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <h3 class="font-semibold text-blue-900 mb-1">What it does</h3>
                    <p class="text-sm text-blue-700">Scans Magento for products without images, searches for matches in ICG API, and validates stock availability.</p>
                </div>
            </div>
        </div>

        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
            <div class="flex items-start">
                <svg class="w-6 h-6 text-yellow-600 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <h3 class="font-semibold text-yellow-900 mb-1">Processing Time</h3>
                    <p class="text-sm text-yellow-700">Analysis runs in background. Depending on catalog size, it may take 5-30 minutes to complete.</p>
                </div>
            </div>
        </div>

        <div class="bg-green-50 border border-green-200 rounded-lg p-6">
            <div class="flex items-start">
                <svg class="w-6 h-6 text-green-600 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <div>
                    <h3 class="font-semibold text-green-900 mb-1">Results</h3>
                    <p class="text-sm text-green-700">Get a detailed CSV report with matched products, stock levels, and recommendations for review.</p>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function newAnalysis() {
    return {
        analysisType: 'no_images',
        showAdvanced: false,
    }
}
</script>
@endpush
@endsection
