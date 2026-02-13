@extends('layouts.app')

@section('title', 'Dashboard Ejecutivo de Ventas')

@section('content')
<div x-data="executiveDashboard()" x-init="init()">
    <div class="mb-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Análisis de Ventas E-Commerce</h1>
                <p class="text-gray-600 mt-1">Reporte Ejecutivo Mensual</p>
            </div>
            <div class="flex items-center space-x-3">
                <a href="{{ route('sales.executive-dashboard.pdf', ['year' => $year]) }}" 
                   class="flex items-center px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Exportar PDF
                </a>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Año</label>
                    <select name="year" onchange="this.form.submit()" 
                            class="w-full border-gray-300 rounded-lg focus:ring-[#0244CD] focus:border-[#0244CD]">
                        @for($y = Carbon\Carbon::now()->year; $y >= 2020; $y--)
                            <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Mes</label>
                    <select name="month" onchange="this.form.submit()"
                            class="w-full border-gray-300 rounded-lg focus:ring-[#0244CD] focus:border-[#0244CD]">
                        <option value="">Todos los meses</option>
                        @foreach(['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'] as $i => $monthName)
                            <option value="{{ $i + 1 }}" {{ $selectedMonth == ($i + 1) ? 'selected' : '' }}>{{ $monthName }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Sucursal</label>
                    <select name="store" onchange="this.form.submit()"
                            class="w-full border-gray-300 rounded-lg focus:ring-[#0244CD] focus:border-[#0244CD]">
                        <option value="">Todas las sucursales</option>
                        @foreach($stores as $store)
                            <option value="{{ $store->source_code }}" {{ $selectedStore == $store->source_code ? 'selected' : '' }}>
                                {{ $store->source_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-end">
                    <a href="{{ route('sales.executive-dashboard') }}" 
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg text-center hover:bg-gray-50 transition">
                        Limpiar Filtros
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="text-sm text-gray-600 mb-1">Total Facturado {{ $year }}</div>
            <div class="text-3xl font-bold text-gray-900">
                B/.{{ number_format($totals['total_revenue'], 2) }}
            </div>
            @if(isset($comparison['revenue_growth']))
            <div class="mt-2 flex items-center text-sm {{ $comparison['revenue_growth'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    @if($comparison['revenue_growth'] >= 0)
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                    @else
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                    @endif
                </svg>
                {{ number_format(abs($comparison['revenue_growth']), 1) }}% vs {{ $compareYear }}
            </div>
            @endif
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="text-sm text-gray-600 mb-1">Total de Pedidos</div>
            <div class="text-3xl font-bold text-gray-900">{{ number_format($totals['total_orders']) }}</div>
            @if(isset($comparison['orders_growth']))
            <div class="mt-2 flex items-center text-sm {{ $comparison['orders_growth'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    @if($comparison['orders_growth'] >= 0)
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                    @else
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                    @endif
                </svg>
                {{ number_format(abs($comparison['orders_growth']), 1) }}% vs {{ $compareYear }}
            </div>
            @endif
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="text-sm text-gray-600 mb-1">Ticket Promedio</div>
            <div class="text-3xl font-bold text-gray-900">
                B/.{{ number_format($totals['average_order_value'], 2) }}
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="text-sm text-gray-600 mb-1">Sucursales Activas</div>
            <div class="text-3xl font-bold text-gray-900">{{ count($totals['stores']) }}</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">VENTAS MENSUALES</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="text-left py-2 text-sm font-medium text-gray-600">Mes</th>
                            <th class="text-right py-2 text-sm font-medium text-gray-600">Monto</th>
                            <th class="text-right py-2 text-sm font-medium text-gray-600">Pedidos</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $totalRevenue = 0; $totalOrders = 0; @endphp
                        @foreach($currentYearData as $monthData)
                        @php 
                            $totalRevenue += $monthData['total_revenue']; 
                            $totalOrders += $monthData['total_orders']; 
                        @endphp
                        <tr class="border-b border-gray-100">
                            <td class="py-2 text-sm text-gray-900">{{ $monthData['month'] }}</td>
                            <td class="py-2 text-sm text-gray-900 text-right">
                                B/.{{ number_format($monthData['total_revenue'], 2) }}
                            </td>
                            <td class="py-2 text-sm text-gray-900 text-right">
                                {{ number_format($monthData['total_orders']) }}
                            </td>
                        </tr>
                        @endforeach
                        <tr class="font-bold bg-gray-50">
                            <td class="py-2 text-sm">Total</td>
                            <td class="py-2 text-sm text-right">B/.{{ number_format($totalRevenue, 2) }}</td>
                            <td class="py-2 text-sm text-right">{{ number_format($totalOrders) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">GRÁFICO DE VENTAS MENSUALES POR MONTO</h3>
            <canvas id="monthlyRevenueChart" height="300"></canvas>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">ACUMULADO DE VENTAS POR SUCURSAL</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="text-left py-2 text-sm font-medium text-gray-600">Sucursal</th>
                            <th class="text-right py-2 text-sm font-medium text-gray-600">Monto</th>
                            <th class="text-right py-2 text-sm font-medium text-gray-600">Pedidos</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($totals['stores'] as $storeData)
                        <tr class="border-b border-gray-100">
                            <td class="py-2 text-sm text-gray-900">{{ $storeData['store_name'] }}</td>
                            <td class="py-2 text-sm text-gray-900 text-right">
                                B/.{{ number_format($storeData['revenue'], 2) }}
                            </td>
                            <td class="py-2 text-sm text-gray-900 text-right">
                                {{ number_format($storeData['orders']) }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">GRÁFICO DE PEDIDOS MENSUALES</h3>
            <canvas id="monthlyOrdersChart" height="300"></canvas>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
        <h3 class="text-lg font-bold text-gray-900 mb-4">
            ANÁLISIS COMPARATIVO: {{ $compareYear }} vs {{ $year }}
        </h3>
        <canvas id="yearComparisonChart" height="120"></canvas>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
function executiveDashboard() {
    return {
        charts: {
            monthlyRevenue: null,
            monthlyOrders: null,
            yearComparison: null
        },
        
        init() {
            this.createMonthlyRevenueChart();
            this.createMonthlyOrdersChart();
            this.createYearComparisonChart();
        },

        createMonthlyRevenueChart() {
            if (this.charts.monthlyRevenue) {
                this.charts.monthlyRevenue.destroy();
            }

            const ctx = document.getElementById('monthlyRevenueChart').getContext('2d');
            const data = @json(array_values($currentYearData));
            
            this.charts.monthlyRevenue = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.map(d => d.month),
                    datasets: [{
                        label: 'Monto (B/.)',
                        data: data.map(d => d.total_revenue),
                        borderColor: '#0244CD',
                        backgroundColor: 'rgba(2, 68, 205, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: value => 'B/.' + value.toLocaleString()
                            }
                        }
                    }
                }
            });
        },

        createMonthlyOrdersChart() {
            if (this.charts.monthlyOrders) {
                this.charts.monthlyOrders.destroy();
            }

            const ctx = document.getElementById('monthlyOrdersChart').getContext('2d');
            const data = @json(array_values($currentYearData));
            
            this.charts.monthlyOrders = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.map(d => d.month),
                    datasets: [{
                        label: 'Pedidos',
                        data: data.map(d => d.total_orders),
                        backgroundColor: '#10B981',
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        },

        createYearComparisonChart() {
            if (this.charts.yearComparison) {
                this.charts.yearComparison.destroy();
            }

            const ctx = document.getElementById('yearComparisonChart').getContext('2d');
            const currentData = @json(array_values($currentYearData));
            const previousData = @json(array_values($previousYearData));
            
            const months = currentData.map(d => d.month);
            
            this.charts.yearComparison = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: months,
                    datasets: [{
                        label: '{{ $compareYear }}',
                        data: previousData.map(d => d.total_revenue),
                        borderColor: '#EF4444',
                        tension: 0.4
                    }, {
                        label: '{{ $year }}',
                        data: currentData.map(d => d.total_revenue),
                        borderColor: '#0244CD',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'top' }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: value => 'B/.' + (value / 1000).toFixed(0) + 'K'
                            }
                        }
                    }
                }
            });
        }
    }
}
</script>
@endpush
@endsection