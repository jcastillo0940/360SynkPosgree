@extends('layouts.app')

@section('title', 'Sales Analysis Dashboard')

@section('content')
<div x-data="salesDashboard()">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Sales Analysis</h1>
                <p class="text-gray-600 mt-1">Comprehensive sales analytics and KPIs</p>
            </div>
            <div class="flex items-center space-x-3">
                <a href="{{ route('sales.create') }}" 
                   class="flex items-center px-4 py-2 bg-[#0244CD] text-white rounded-lg hover:bg-[#0244CD]/90 transition">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    New Analysis
                </a>
            </div>
        </div>
    </div>

    @if($period)
    <!-- Filtros -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Tipo de Periodo -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Period Type</label>
                <select x-model="periodType" @change="changePeriodType()" 
                        class="w-full border-gray-300 rounded-lg focus:ring-[#0244CD] focus:border-[#0244CD]">
                    <option value="daily" {{ $periodType === 'daily' ? 'selected' : '' }}>Daily</option>
                    <option value="weekly" {{ $periodType === 'weekly' ? 'selected' : '' }}>Weekly</option>
                    <option value="monthly" {{ $periodType === 'monthly' ? 'selected' : '' }}>Monthly</option>
                    <option value="quarterly" {{ $periodType === 'quarterly' ? 'selected' : '' }}>Quarterly</option>
                    <option value="yearly" {{ $periodType === 'yearly' ? 'selected' : '' }}>Yearly</option>
                </select>
            </div>

            <!-- Selector de Periodo -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Select Period</label>
                <select x-model="selectedPeriod" @change="changePeriod()" 
                        class="w-full border-gray-300 rounded-lg focus:ring-[#0244CD] focus:border-[#0244CD]">
                    @foreach($availablePeriods as $p)
                    <option value="{{ $p->id }}" {{ $p->id == $period->id ? 'selected' : '' }}>
                        {{ $p->period_start->format('M d, Y') }} - {{ $p->period_end->format('M d, Y') }}
                    </option>
                    @endforeach
                </select>
            </div>

            <!-- Periodo Actual -->
            <div class="flex items-end">
                <div class="text-sm text-gray-600">
                    <div class="font-medium">Current Period</div>
                    <div>{{ $period->period_start->format('M d, Y') }} - {{ $period->period_end->format('M d, Y') }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- KPIs Principales -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <!-- Total Revenue -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm text-gray-600">Total Revenue</span>
                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="text-3xl font-bold text-gray-900">${{ number_format($period->total_revenue, 2) }}</div>
            @if($previousPeriod)
            <div class="mt-2 flex items-center text-sm">
                @php
                    $change = $previousPeriod->total_revenue > 0 
                        ? (($period->total_revenue - $previousPeriod->total_revenue) / $previousPeriod->total_revenue) * 100 
                        : 0;
                @endphp
                @if($change > 0)
                    <svg class="w-4 h-4 text-green-500 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                    <span class="text-green-600">+{{ number_format($change, 1) }}%</span>
                @else
                    <svg class="w-4 h-4 text-red-500 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/>
                    </svg>
                    <span class="text-red-600">{{ number_format($change, 1) }}%</span>
                @endif
                <span class="text-gray-500 ml-1">vs previous period</span>
            </div>
            @endif
        </div>

        <!-- Total Orders -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm text-gray-600">Total Orders</span>
                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                </svg>
            </div>
            <div class="text-3xl font-bold text-gray-900">{{ number_format($period->total_orders) }}</div>
            @if($previousPeriod)
            <div class="mt-2 flex items-center text-sm">
                @php
                    $change = $previousPeriod->total_orders > 0 
                        ? (($period->total_orders - $previousPeriod->total_orders) / $previousPeriod->total_orders) * 100 
                        : 0;
                @endphp
                @if($change > 0)
                    <span class="text-green-600">+{{ number_format($change, 1) }}%</span>
                @else
                    <span class="text-red-600">{{ number_format($change, 1) }}%</span>
                @endif
                <span class="text-gray-500 ml-1">vs previous period</span>
            </div>
            @endif
        </div>

        <!-- Average Order Value -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm text-gray-600">Average Order Value</span>
                <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
            </div>
            <div class="text-3xl font-bold text-gray-900">${{ number_format($period->average_order_value, 2) }}</div>
            @if($previousPeriod)
            <div class="mt-2 flex items-center text-sm">
                @php
                    $change = $previousPeriod->average_order_value > 0 
                        ? (($period->average_order_value - $previousPeriod->average_order_value) / $previousPeriod->average_order_value) * 100 
                        : 0;
                @endphp
                @if($change > 0)
                    <span class="text-green-600">+{{ number_format($change, 1) }}%</span>
                @else
                    <span class="text-red-600">{{ number_format($change, 1) }}%</span>
                @endif
                <span class="text-gray-500 ml-1">vs previous period</span>
            </div>
            @endif
        </div>

        <!-- Total Customers -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm text-gray-600">Total Customers</span>
                <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
            </div>
            <div class="text-3xl font-bold text-gray-900">{{ number_format($period->total_customers) }}</div>
            <div class="mt-2 text-sm text-gray-600">
                <span class="text-green-600">{{ number_format($period->new_customers) }}</span> new, 
                <span class="text-blue-600">{{ number_format($period->returning_customers) }}</span> returning
            </div>
        </div>
    </div>

    <!-- KPIs de Retención -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
        <h2 class="text-lg font-bold text-gray-900 mb-4">Retention & Loyalty Metrics</h2>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div>
                <div class="text-sm text-gray-600 mb-1">Repeat Purchase Rate</div>
                <div class="text-2xl font-bold text-gray-900">{{ number_format($period->repeat_purchase_rate, 1) }}%</div>
                <div class="mt-2 h-2 bg-gray-200 rounded-full overflow-hidden">
                    <div class="h-full bg-blue-500" style="width: {{ $period->repeat_purchase_rate }}%"></div>
                </div>
            </div>

            <div>
                <div class="text-sm text-gray-600 mb-1">Customer Lifetime Value</div>
                <div class="text-2xl font-bold text-gray-900">${{ number_format($period->customer_lifetime_value, 2) }}</div>
            </div>

            <div>
                <div class="text-sm text-gray-600 mb-1">Cart Abandonment Rate</div>
                <div class="text-2xl font-bold text-gray-900">{{ number_format($period->cart_abandonment_rate, 1) }}%</div>
                <div class="mt-2 h-2 bg-gray-200 rounded-full overflow-hidden">
                    <div class="h-full bg-red-500" style="width: {{ $period->cart_abandonment_rate }}%"></div>
                </div>
            </div>

            <div>
                <div class="text-sm text-gray-600 mb-1">Conversion Rate</div>
                <div class="text-2xl font-bold text-gray-900">
                    {{ $period->conversion_rate ? number_format($period->conversion_rate, 1) . '%' : 'N/A' }}
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Revenue por Tienda -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Revenue by Store</h3>
            <div class="space-y-3">
                @foreach($period->salesByStore->take(5) as $store)
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-sm font-medium text-gray-700">{{ $store->store_name }}</span>
                        <span class="text-sm font-bold text-gray-900">${{ number_format($store->total_revenue, 2) }}</span>
                    </div>
                    <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                        @php
                            $percentage = $period->total_revenue > 0 ? ($store->total_revenue / $period->total_revenue) * 100 : 0;
                        @endphp
                        <div class="h-full bg-blue-500" style="width: {{ $percentage }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>
            <a href="{{ route('sales.by-store', ['period_id' => $period->id]) }}" 
               class="mt-4 text-sm text-[#0244CD] hover:underline inline-block">
                View all stores →
            </a>
        </div>

        <!-- Métodos de Pago -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Payment Methods</h3>
            <div class="space-y-3">
                @foreach($period->salesByPaymentMethod->take(5) as $payment)
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-sm font-medium text-gray-700">{{ $payment->payment_method_title }}</span>
                        <span class="text-sm font-bold text-gray-900">{{ number_format($payment->percentage, 1) }}%</span>
                    </div>
                    <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                        <div class="h-full bg-green-500" style="width: {{ $payment->percentage }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>
            <a href="{{ route('sales.by-payment', ['period_id' => $period->id]) }}" 
               class="mt-4 text-sm text-[#0244CD] hover:underline inline-block">
                View all methods →
            </a>
        </div>
    </div>

    <!-- Top Productos -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-gray-900">Top 10 Products</h3>
            <a href="{{ route('sales.top-products', ['period_id' => $period->id]) }}" 
               class="text-sm text-[#0244CD] hover:underline">
                View all →
            </a>
        </div>
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
                    @foreach($period->topProducts->take(10) as $index => $product)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm text-gray-900">{{ $index + 1 }}</td>
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

    <!-- Enlaces Rápidos -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <a href="{{ route('sales.by-location', ['period_id' => $period->id]) }}" 
           class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-bold text-gray-900">Sales by Location</h3>
                    <p class="text-sm text-gray-600 mt-1">Geographic analysis</p>
                </div>
                <svg class="w-8 h-8 text-[#0244CD]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
        </a>

        <a href="{{ route('sales.cohorts', ['period_id' => $period->id]) }}" 
           class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-bold text-gray-900">Customer Cohorts</h3>
                    <p class="text-sm text-gray-600 mt-1">Retention analysis</p>
                </div>
                <svg class="w-8 h-8 text-[#0244CD]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
            </div>
        </a>

        <a href="{{ route('sales.compare') }}" 
           class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-bold text-gray-900">Compare Periods</h3>
                    <p class="text-sm text-gray-600 mt-1">Side-by-side comparison</p>
                </div>
                <svg class="w-8 h-8 text-[#0244CD]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
            </div>
        </a>
    </div>

    @else
    <!-- No hay datos -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
        <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
        </svg>
        <h3 class="text-lg font-medium text-gray-900 mb-2">No analysis available</h3>
        <p class="text-gray-600 mb-4">Create your first sales analysis to see insights</p>
        <a href="{{ route('sales.create') }}" 
           class="inline-flex items-center px-4 py-2 bg-[#0244CD] text-white rounded-lg hover:bg-[#0244CD]/90 transition">
            Create Analysis
        </a>
    </div>
    @endif
</div>

<script>
function salesDashboard() {
    return {
        periodType: '{{ $periodType }}',
        selectedPeriod: '{{ $period->id ?? '' }}',
        
        changePeriodType() {
            window.location.href = `{{ route('sales.dashboard') }}?period_type=${this.periodType}`;
        },
        
        changePeriod() {
            window.location.href = `{{ route('sales.dashboard') }}?period_id=${this.selectedPeriod}`;
        }
    }
}
</script>
@endsection
