@extends('layouts.app')

@section('title', 'Análisis de Cohortes de Clientes')

@section('content')
<div class="mb-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Análisis de Cohortes</h1>
            <p class="text-gray-600 mt-1">Retención de clientes y comportamiento de compra a través del tiempo</p>
        </div>
        <div class="flex items-center space-x-3">
            <a href="{{ route('sales.dashboard', ['period_id' => $periodId]) }}" 
               class="flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Volver al Dashboard
            </a>
        </div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
    <form method="GET" action="{{ route('sales.cohorts') }}" class="flex items-end space-x-4">
        <div class="flex-1">
            <label class="block text-sm font-medium text-gray-700 mb-2">Seleccionar Periodo de Análisis</label>
            <select name="period_id" onchange="this.form.submit()" 
                    class="w-full border-gray-300 rounded-lg focus:ring-[#0244CD] focus:border-[#0244CD]">
                @foreach($periods as $p)
                    <option value="{{ $p->id }}" {{ $periodId == $p->id ? 'selected' : '' }}>
                        {{ $p->period_label }}: {{ $p->period_start->format('M d, Y') }} - {{ $p->period_end->format('M d, Y') }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="text-sm text-gray-500 pb-2">
            Mostrando datos de retención mensual por grupo de adquisición.
        </div>
    </form>
</div>

@if($cohorts->isEmpty())
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
        <p class="text-gray-600">No hay datos de cohortes disponibles para este periodo.</p>
    </div>
@else
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-bold text-gray-900">Matriz de Retención de Clientes (%)</h3>
            <p class="text-sm text-gray-500">Porcentaje de clientes que realizaron una compra repetida en los meses posteriores.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full border-collapse">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase border-b">Cohorte (Mes)</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase border-b">Clientes</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase border-b">Mes 0</th>
                        @for($i = 1; $i <= 12; $i++)
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase border-b">Mes {{ $i }}</th>
                        @endfor
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($cohorts as $cohort)
                        @php 
                            $retention = json_decode($cohort->retention_by_month, true) ?? [];
                        @endphp
                        <tr>
                            <td class="px-4 py-4 whitespace-nowrap text-sm font-bold text-gray-900 bg-gray-50">
                                {{ Carbon\Carbon::parse($cohort->cohort_month)->format('M Y') }}
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-center font-medium text-blue-600 border-r">
                                {{ number_format($cohort->customers_count) }}
                            </td>
                            <td class="px-4 py-4 text-center text-sm bg-blue-500 text-white font-bold">
                                100%
                            </td>
                            @for($i = 1; $i <= 12; $i++)
                                @php 
                                    $value = $retention[$i] ?? null;
                                    $opacity = $value ? max(0.1, $value / 100) : 0;
                                @endphp
                                <td class="px-4 py-4 text-center text-sm border-l" 
                                    @if($value !== null) style="background-color: rgba(2, 68, 205, {{ $opacity }}); color: {{ $value > 40 ? 'white' : 'black' }};" @endif>
                                    {{ $value !== null ? number_format($value, 1) . '%' : '-' }}
                                </td>
                            @endfor
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Ingresos Iniciales por Cohorte</h3>
            <div class="space-y-4">
                @foreach($cohorts->take(6) as $cohort)
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="font-medium text-gray-700">{{ Carbon\Carbon::parse($cohort->cohort_month)->format('M Y') }}</span>
                            <span class="font-bold text-gray-900">B/.{{ number_format($cohort->initial_revenue, 2) }}</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2">
                            @php 
                                $maxRevenue = $cohorts->max('initial_revenue') ?: 1;
                                $width = ($cohort->initial_revenue / $maxRevenue) * 100;
                            @endphp
                            <div class="bg-[#0244CD] h-2 rounded-full" style="width: {{ $width }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 flex flex-col justify-center">
            <div class="text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-100 text-[#0244CD] rounded-full mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">Interpretación de Cohortes</h3>
                <p class="text-sm text-gray-600 px-6">
                    Las cohortes te permiten identificar si la calidad de tus clientes mejora con el tiempo. 
                    Si los porcentajes en los meses a la derecha (Mes 1, 2, 3...) aumentan en las cohortes más recientes, 
                    tus estrategias de fidelización están funcionando.
                </p>
            </div>
        </div>
    </div>
@endif

@endsection