@extends('layouts.app')

@section('content')
<div class="mb-6">
    <h1 class="text-3xl font-bold">Análisis Acumulado de Productos</h1>
    
    <div class="flex space-x-2 mt-4">
        <a href="{{ request()->fullUrlWithQuery(['view_type' => 'period']) }}" 
           class="px-4 py-2 rounded-lg {{ $viewType == 'period' ? 'bg-blue-600 text-white' : 'bg-gray-200' }}">Por Mes</a>
        <a href="{{ request()->fullUrlWithQuery(['view_type' => 'year']) }}" 
           class="px-4 py-2 rounded-lg {{ $viewType == 'year' ? 'bg-blue-600 text-white' : 'bg-gray-200' }}">Por Año</a>
        <a href="{{ request()->fullUrlWithQuery(['view_type' => 'total']) }}" 
           class="px-4 py-2 rounded-lg {{ $viewType == 'total' ? 'bg-blue-600 text-white' : 'bg-gray-200' }}">Histórico Total</a>
    </div>
</div>

<div class="bg-white p-6 rounded-xl shadow-sm mb-6 border">
    <form method="GET" action="{{ route('sales.top-products') }}" class="flex flex-wrap gap-4 items-end bg-white p-6 rounded-xl shadow-sm border">
    
    <div class="w-40">
        <label class="block text-sm font-medium text-gray-700 mb-1">Año</label>
        <select name="year" onchange="this.form.month.value=''; this.form.submit()" 
                class="w-full border-gray-300 rounded-lg focus:ring-blue-500">
            <option value="">Seleccione Año</option>
            @foreach($years as $year)
                <option value="{{ $year }}" {{ $selectedYear == $year ? 'selected' : '' }}>{{ $year }}</option>
            @endforeach
        </select>
    </div>

    <div class="w-48">
        <label class="block text-sm font-medium text-gray-700 mb-1">Mes</label>
        <select name="month" onchange="this.form.submit()" 
                {{ empty($selectedYear) ? 'disabled' : '' }}
                class="w-full border-gray-300 rounded-lg focus:ring-blue-500 {{ empty($selectedYear) ? 'bg-gray-100 cursor-not-allowed' : '' }}">
            <option value="">Todos los meses</option>
            @foreach($months as $m)
                <option value="{{ $m['num'] }}" {{ $selectedMonth == $m['num'] ? 'selected' : '' }}>
                    {{ ucfirst($m['name']) }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="w-32">
        <label class="block text-sm font-medium text-gray-700 mb-1">Top</label>
        <select name="limit" onchange="this.form.submit()" class="w-full border-gray-300 rounded-lg focus:ring-blue-500">
            @foreach([10, 25, 50, 100] as $l)
                <option value="{{ $l }}" {{ $limit == $l ? 'selected' : '' }}>{{ $l }}</option>
            @endforeach
        </select>
    </div>

    @if($selectedYear)
        <a href="{{ route('sales.top-products') }}" class="text-sm text-red-600 hover:underline mb-2">Limpiar filtros</a>
    @endif
</form>
</div>

<div class="bg-white rounded-xl shadow border overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Producto</th>
                @php $cols = ['quantity_sold' => 'Cant.', 'total_revenue' => 'Ingresos', 'average_price' => 'Precio Prom.']; @endphp
                @foreach($cols as $field => $label)
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                    <a href="{{ request()->fullUrlWithQuery(['sort_by' => $field, 'sort_order' => ($sortBy == $field && $sortOrder == 'desc') ? 'asc' : 'desc']) }}" class="flex items-center justify-end hover:text-blue-600">
                        {{ $label }}
                        @if($sortBy == $field)
                            {!! $sortOrder == 'desc' ? '↓' : '↑' !!}
                        @endif
                    </a>
                </th>
                @endforeach
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @foreach($products as $index => $product)
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4 text-sm text-gray-500">{{ $index + 1 }}</td>
                <td class="px-6 py-4">
                    <div class="font-medium">{{ $product->product_name }}</div>
                    <div class="text-xs text-gray-400">{{ $product->sku }}</div>
                </td>

                <td class="px-6 py-4 text-right font-bold">{{ number_format($product->quantity_sold) }}</td>
                <td class="px-6 py-4 text-right text-blue-600 font-bold">B/.{{ number_format($product->total_revenue, 2) }}</td>
                <td class="px-6 py-4 text-right text-gray-500">B/.{{ number_format($product->average_price, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection