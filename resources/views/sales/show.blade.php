@extends('layouts.app')

@section('title', 'Sales Analysis Details')

@section('content')
<div x-data="salesAnalysisDetail({{ $period->id }})">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <div class="flex items-center space-x-3">
                    <h1 class="text-3xl font-bold text-gray-900">Sales Analysis #{{ $period->id }}</h1>
                    <span class="px-3 py-1 text-sm font-medium rounded-full
                        @if($period->status === 'completed') bg-green-100 text-green-800
                        @elseif($period->status === 'processing') bg-blue-100 text-blue-800
                        @elseif($period->status === 'failed') bg-red-100 text-red-800
                        @else bg-gray-100 text-gray-800
                        @endif">
                        <span x-text="status"></span>
                    </span>
                </div>
                <p class="text-gray-600 mt-1">
                    {{ $period->period_label }} · 
                    {{ $period->period_start->format('M d, Y') }} - {{ $period->period_end->format('M d, Y') }}
                </p>
            </div>
            <div class="flex items-center space-x-3">
                @if($period->status === 'completed')
                <a href="{{ route('sales.export', $period->id) }}" 
                   class="flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Export CSV
                </a>
                @endif
                <a href="{{ route('sales.index') }}" 
                   class="flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Back to List
                </a>
            </div>
        </div>
    </div>

    @if($period->status === 'processing')
    <!-- Processing Status -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6">
        <div class="flex items-center">
            <svg class="animate-spin h-8 w-8 text-blue-600 mr-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <div>
                <h3 class="text-lg font-bold text-blue-900">Analysis in Progress</h3>
                <p class="text-blue-700 text-sm">Processing sales data... This page will auto-refresh when complete.</p>
            </div>
        </div>
    </div>
    @elseif($period->status === 'failed')
    <!-- Failed Status -->
    <div class="bg-red-50 border border-red-200 rounded-lg p-6 mb-6">
        <div class="flex items-center">
            <svg class="h-8 w-8 text-red-600 mr-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>
                <h3 class="text-lg font-bold text-red-900">Analysis Failed</h3>
                <p class="text-red-700 text-sm">{{ $period->error_message }}</p>
            </div>
        </div>
    </div>
    @endif

    @if($period->status === 'completed')
    <!-- Resumen de KPIs -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="text-sm text-gray-600 mb-1">Total Revenue</div>
            <div class="text-3xl font-bold text-gray-900">${{ number_format($period->total_revenue, 2) }}</div>
        </div>
        
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="text-sm text-gray-600 mb-1">Total Orders</div>
            <div class="text-3xl font-bold text-gray-900">{{ number_format($period->total_orders) }}</div>
        </div>
        
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="text-sm text-gray-600 mb-1">Average Order Value</div>
            <div class="text-3xl font-bold text-gray-900">${{ number_format($period->average_order_value, 2) }}</div>
        </div>
        
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="text-sm text-gray-600 mb-1">Total Customers</div>
            <div class="text-3xl font-bold text-gray-900">{{ number_format($period->total_customers) }}</div>
            <div class="text-xs text-gray-500 mt-1">
                {{ $period->new_customers }} new, {{ $period->returning_customers }} returning
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-6">
        <div class="border-b border-gray-200">
            <nav class="flex -mb-px">
                <button @click="currentTab = 'overview'" 
                        :class="currentTab === 'overview' ? 'border-[#0244CD] text-[#0244CD]' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="px-6 py-4 border-b-2 font-medium text-sm transition">
                    Overview
                </button>
                <button @click="currentTab = 'stores'" 
                        :class="currentTab === 'stores' ? 'border-[#0244CD] text-[#0244CD]' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="px-6 py-4 border-b-2 font-medium text-sm transition">
                    By Store
                </button>
                <button @click="currentTab = 'payments'" 
                        :class="currentTab === 'payments' ? 'border-[#0244CD] text-[#0244CD]' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="px-6 py-4 border-b-2 font-medium text-sm transition">
                    Payment Methods
                </button>
                <button @click="currentTab = 'locations'" 
                        :class="currentTab === 'locations' ? 'border-[#0244CD] text-[#0244CD]' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="px-6 py-4 border-b-2 font-medium text-sm transition">
                    Locations
                </button>
                <button @click="currentTab = 'products'" 
                        :class="currentTab === 'products' ? 'border-[#0244CD] text-[#0244CD]' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="px-6 py-4 border-b-2 font-medium text-sm transition">
                    Top Products
                </button>
            </nav>
        </div>

        <div class="p-6">
            <!-- Tab: Overview -->
            <div x-show="currentTab === 'overview'">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 mb-4">Customer Metrics</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Repeat Purchase Rate</span>
                                <span class="font-medium">{{ number_format($period->repeat_purchase_rate, 1) }}%</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Customer Lifetime Value</span>
                                <span class="font-medium">${{ number_format($period->customer_lifetime_value, 2) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">New Customers</span>
                                <span class="font-medium">{{ number_format($period->new_customers) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Returning Customers</span>
                                <span class="font-medium">{{ number_format($period->returning_customers) }}</span>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-lg font-bold text-gray-900 mb-4">Cart & Conversion</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Carts Created</span>
                                <span class="font-medium">{{ number_format($period->carts_created) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Carts Abandoned</span>
                                <span class="font-medium">{{ number_format($period->carts_abandoned) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Abandonment Rate</span>
                                <span class="font-medium">{{ number_format($period->cart_abandonment_rate, 1) }}%</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Conversion Rate</span>
                                <span class="font-medium">{{ $period->conversion_rate ? number_format($period->conversion_rate, 1) . '%' : 'N/A' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab: Stores -->
            <div x-show="currentTab === 'stores'">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Store</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Orders</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Revenue</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">AOV</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Items</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($period->salesByStore as $store)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $store->store_name }}</td>
                                <td class="px-4 py-3 text-sm text-right text-gray-900">{{ number_format($store->total_orders) }}</td>
                                <td class="px-4 py-3 text-sm text-right font-medium text-gray-900">${{ number_format($store->total_revenue, 2) }}</td>
                                <td class="px-4 py-3 text-sm text-right text-gray-600">${{ number_format($store->average_order_value, 2) }}</td>
                                <td class="px-4 py-3 text-sm text-right text-gray-600">{{ number_format($store->total_items_sold) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab: Payments -->
            <div x-show="currentTab === 'payments'">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Payment Method</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Orders</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Revenue</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">% of Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($period->salesByPaymentMethod as $payment)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $payment->payment_method_title }}</td>
                                <td class="px-4 py-3 text-sm text-right text-gray-900">{{ number_format($payment->total_orders) }}</td>
                                <td class="px-4 py-3 text-sm text-right font-medium text-gray-900">${{ number_format($payment->total_revenue, 2) }}</td>
                                <td class="px-4 py-3 text-sm text-right text-gray-600">{{ number_format($payment->percentage, 1) }}%</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab: Locations -->
            <div x-show="currentTab === 'locations'">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Location</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Orders</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Revenue</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Customers</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($period->salesByLocation->take(20) as $location)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm text-gray-900">
                                    {{ $location->city }}, {{ $location->region }}, {{ $location->country }}
                                </td>
                                <td class="px-4 py-3 text-sm text-right text-gray-900">{{ number_format($location->total_orders) }}</td>
                                <td class="px-4 py-3 text-sm text-right font-medium text-gray-900">${{ number_format($location->total_revenue, 2) }}</td>
                                <td class="px-4 py-3 text-sm text-right text-gray-600">{{ number_format($location->total_customers) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab: Products -->
            <div x-show="currentTab === 'products'">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Qty Sold</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Revenue</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Avg Price</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($period->topProducts->take(20) as $index => $product)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm text-gray-600">{{ $index + 1 }}</td>
                                <td class="px-4 py-3">
                                    <div class="text-sm font-medium text-gray-900">{{ $product->product_name }}</div>
                                    <div class="text-xs text-gray-500">{{ $product->sku }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm text-right text-gray-900">{{ number_format($product->quantity_sold) }}</td>
                                <td class="px-4 py-3 text-sm text-right font-medium text-gray-900">${{ number_format($product->total_revenue, 2) }}</td>
                                <td class="px-4 py-3 text-sm text-right text-gray-600">${{ number_format($product->average_price, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Información Adicional -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Analysis Information</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div>
                <span class="text-gray-600">Created:</span>
                <span class="font-medium text-gray-900 ml-2">{{ $period->created_at->format('M d, Y H:i') }}</span>
            </div>
            @if($period->started_at)
            <div>
                <span class="text-gray-600">Started:</span>
                <span class="font-medium text-gray-900 ml-2">{{ $period->started_at->format('M d, Y H:i') }}</span>
            </div>
            @endif
            @if($period->completed_at)
            <div>
                <span class="text-gray-600">Completed:</span>
                <span class="font-medium text-gray-900 ml-2">{{ $period->completed_at->format('M d, Y H:i') }}</span>
            </div>
            @endif
            @if($period->duration)
            <div>
                <span class="text-gray-600">Duration:</span>
                <span class="font-medium text-gray-900 ml-2">{{ $period->duration }}</span>
            </div>
            @endif
        </div>
    </div>
</div>

<script>
function salesAnalysisDetail(periodId) {
    return {
        currentTab: 'overview',
        status: '{{ $period->status_label }}',
        
        init() {
            @if($period->status === 'processing')
            this.pollStatus();
            @endif
        },
        
        pollStatus() {
            const interval = setInterval(() => {
                fetch(`/sales/${periodId}/status`)
                    .then(response => response.json())
                    .then(data => {
                        this.status = data.status.charAt(0).toUpperCase() + data.status.slice(1);
                        
                        if (data.status === 'completed' || data.status === 'failed') {
                            clearInterval(interval);
                            window.location.reload();
                        }
                    });
            }, 5000); // Poll every 5 seconds
        }
    }
}
</script>
@endsection