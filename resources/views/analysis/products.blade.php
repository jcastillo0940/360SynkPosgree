@extends('layouts.app')

@section('title', 'Analyzed Products')

@section('content')
<div x-data="productsIndex()">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Analyzed Products</h1>
                <p class="text-gray-600 mt-1">Review and manage products found in analysis</p>
            </div>
            <div class="flex items-center space-x-3">
                <a href="{{ route('analysis.export') }}?{{ http_build_query(request()->all()) }}" 
                   class="flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Export CSV
                </a>
                <a href="{{ route('analysis.index') }}" 
                   class="flex items-center px-4 py-2 bg-[#0244CD] text-white rounded-lg hover:bg-[#0244CD]/90 transition">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white border border-gray-200 rounded-lg p-4">
            <p class="text-sm text-gray-600">Total Products</p>
            <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['total']) }}</p>
        </div>
        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
            <p class="text-sm text-green-700">Matched</p>
            <p class="text-2xl font-bold text-green-900">{{ number_format($stats['matched']) }}</p>
        </div>
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <p class="text-sm text-blue-700">With Stock</p>
            <p class="text-2xl font-bold text-blue-900">{{ number_format($stats['with_stock']) }}</p>
        </div>
        <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
            <p class="text-sm text-orange-700">Pending Review</p>
            <p class="text-2xl font-bold text-orange-900">{{ number_format($stats['pending']) }}</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
        <form method="GET" action="{{ route('analysis.products') }}" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Search -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" 
                           placeholder="SKU or name..."
                           class="w-full border-gray-300 rounded-lg focus:ring-[#0244CD] focus:border-[#0244CD]">
                </div>

                <!-- Match Status -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Match Status</label>
                    <select name="match_status" class="w-full border-gray-300 rounded-lg focus:ring-[#0244CD] focus:border-[#0244CD]">
                        <option value="">All</option>
                        <option value="exact" {{ request('match_status') === 'exact' ? 'selected' : '' }}>Exact Match</option>
                        <option value="approximate" {{ request('match_status') === 'approximate' ? 'selected' : '' }}>Approximate</option>
                        <option value="not_found" {{ request('match_status') === 'not_found' ? 'selected' : '' }}>Not Found</option>
                    </select>
                </div>

                <!-- Review Status -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Review Status</label>
                    <select name="status" class="w-full border-gray-300 rounded-lg focus:ring-[#0244CD] focus:border-[#0244CD]">
                        <option value="">All</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="reviewed" {{ request('status') === 'reviewed' ? 'selected' : '' }}>Reviewed</option>
                        <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                    </select>
                </div>

                <!-- Has Stock -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Stock</label>
                    <select name="has_stock" class="w-full border-gray-300 rounded-lg focus:ring-[#0244CD] focus:border-[#0244CD]">
                        <option value="">All</option>
                        <option value="yes" {{ request('has_stock') === 'yes' ? 'selected' : '' }}>With Stock</option>
                        <option value="no" {{ request('has_stock') === 'no' ? 'selected' : '' }}>Without Stock</option>
                    </select>
                </div>
            </div>

            <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                <a href="{{ route('analysis.products') }}" class="text-sm text-gray-600 hover:text-gray-900">
                    Clear Filters
                </a>
                <button type="submit" 
                        class="px-6 py-2 bg-[#0244CD] text-white rounded-lg hover:bg-[#0244CD]/90 transition">
                    Apply Filters
                </button>
            </div>
        </form>
    </div>

    <!-- Products Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-bold text-gray-900">Products</h2>
                <span class="text-sm text-gray-600">{{ $products->total() }} results</span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <input type="checkbox" @change="toggleAll($event)" class="rounded border-gray-300">
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SKU</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Match</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ICG Product</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($products as $product)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <input type="checkbox" x-model="selected" :value="{{ $product->id }}" class="rounded border-gray-300">
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ $product->magento_sku }}
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <div class="max-w-xs">
                                <p class="font-medium text-gray-900 truncate" title="{{ $product->magento_name }}">
                                    {{ $product->magento_name }}
                                </p>
                                <p class="text-xs text-gray-500">{{ $product->magento_category_name }}</p>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <div class="flex items-center space-x-2">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full
                                    {{ $product->match_status === 'exact' ? 'bg-green-100 text-green-800' : '' }}
                                    {{ $product->match_status === 'approximate' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                    {{ $product->match_status === 'not_found' ? 'bg-red-100 text-red-800' : '' }}">
                                    {{ $product->match_status_label }}
                                </span>
                                @if($product->match_score)
                                <span class="text-xs text-gray-500">{{ number_format($product->match_score, 1) }}%</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm">
                            @if($product->icg_webname || $product->icg_description)
                            <div class="max-w-xs">
                                <p class="text-gray-900 truncate" title="{{ $product->icg_webname }}">
                                    {{ $product->icg_webname ?? $product->icg_description }}
                                </p>
                                @if($product->icg_barcode)
                                <p class="text-xs text-gray-500">{{ $product->icg_barcode }}</p>
                                @endif
                            </div>
                            @else
                            <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            @if($product->meets_stock_criteria)
                            <span class="px-2 py-1 text-xs font-semibold bg-green-100 text-green-800 rounded">
                                {{ $product->total_stock }} units
                            </span>
                            @else
                            <span class="px-2 py-1 text-xs font-semibold bg-gray-100 text-gray-800 rounded">
                                {{ $product->total_stock }} units
                            </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-{{ $product->status_color }}-100 text-{{ $product->status_color }}-800">
                                {{ $product->status_label }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm space-x-2">
                            <a href="{{ route('analysis.product-detail', $product->id) }}" 
                               class="text-[#0244CD] hover:text-[#F6D101]" title="View Details">
                                <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                            No products found matching your filters
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($products->hasPages())
        <div class="p-6 border-t border-gray-200">
            {{ $products->links() }}
        </div>
        @endif
    </div>

    <!-- Bulk Actions -->
    <div x-show="selected.length > 0" 
         class="fixed bottom-6 left-1/2 transform -translate-x-1/2 bg-white rounded-lg shadow-lg border border-gray-200 p-4">
        <div class="flex items-center space-x-4">
            <span class="text-sm text-gray-600">
                <span x-text="selected.length"></span> selected
            </span>
            <form method="POST" action="{{ route('analysis.bulk-update') }}" class="flex items-center space-x-2">
                @csrf
                <input type="hidden" name="product_ids" :value="JSON.stringify(selected)">
                <button type="submit" name="action" value="approve" 
                        class="px-4 py-2 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700">
                    Approve
                </button>
                <button type="submit" name="action" value="reject" 
                        class="px-4 py-2 bg-red-600 text-white text-sm rounded-lg hover:bg-red-700">
                    Reject
                </button>
                <button type="button" @click="selected = []" 
                        class="px-4 py-2 bg-gray-200 text-gray-700 text-sm rounded-lg hover:bg-gray-300">
                    Cancel
                </button>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function productsIndex() {
    return {
        selected: [],
        
        toggleAll(event) {
            const checkboxes = document.querySelectorAll('input[type="checkbox"][x-model="selected"]');
            if (event.target.checked) {
                this.selected = Array.from(checkboxes).map(cb => parseInt(cb.value));
            } else {
                this.selected = [];
            }
        }
    }
}
</script>
@endpush
@endsection
