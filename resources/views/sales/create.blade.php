@extends('layouts.app')

@section('title', 'Create Sales Analysis')

@section('content')
<div class="max-w-3xl mx-auto">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Create Sales Analysis</h1>
                <p class="text-gray-600 mt-1">Generate comprehensive sales analytics for a specific period</p>
            </div>
            <a href="{{ route('sales.index') }}" 
               class="flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back
            </a>
        </div>
    </div>

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg mb-6">
        <ul class="list-disc list-inside">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <!-- Formulario -->
    <form action="{{ route('sales.execute') }}" method="POST" x-data="salesAnalysisForm()">
        @csrf

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-6">
            
            <!-- Tipo de Periodo -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Period Type <span class="text-red-500">*</span>
                </label>
                <select name="period_type" 
                        x-model="periodType"
                        @change="updateDates()"
                        required
                        class="w-full border-gray-300 rounded-lg focus:ring-[#0244CD] focus:border-[#0244CD]">
                    <option value="">Select period type</option>
                    <option value="daily">Daily</option>
                    <option value="weekly">Weekly</option>
                    <option value="monthly">Monthly</option>
                    <option value="quarterly">Quarterly</option>
                    <option value="yearly">Yearly</option>
                </select>
                <p class="mt-1 text-xs text-gray-500">
                    Choose the time period granularity for analysis
                </p>
            </div>

            <!-- Quick Presets -->
            <div x-show="periodType" x-cloak>
                <label class="block text-sm font-medium text-gray-700 mb-2">Quick Presets</label>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <button type="button" 
                            @click="setPreset('last_week')"
                            class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 text-sm">
                        Last Week
                    </button>
                    <button type="button" 
                            @click="setPreset('last_month')"
                            class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 text-sm">
                        Last Month
                    </button>
                    <button type="button" 
                            @click="setPreset('last_quarter')"
                            class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 text-sm">
                        Last Quarter
                    </button>
                    <button type="button" 
                            @click="setPreset('last_year')"
                            class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 text-sm">
                        Last Year
                    </button>
                </div>
            </div>

            <!-- Rango de Fechas -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Start Date <span class="text-red-500">*</span>
                    </label>
                    <input type="date" 
                           name="start_date" 
                           x-model="startDate"
                           required
                           class="w-full border-gray-300 rounded-lg focus:ring-[#0244CD] focus:border-[#0244CD]">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        End Date <span class="text-red-500">*</span>
                    </label>
                    <input type="date" 
                           name="end_date" 
                           x-model="endDate"
                           required
                           class="w-full border-gray-300 rounded-lg focus:ring-[#0244CD] focus:border-[#0244CD]">
                </div>
            </div>

            <!-- Información de lo que se analizará -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex">
                    <svg class="w-5 h-5 text-blue-600 mr-3 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div class="text-sm text-blue-800">
                        <p class="font-medium mb-1">This analysis will include:</p>
                        <ul class="list-disc list-inside space-y-1 text-blue-700">
                            <li>Total revenue, orders, and AOV</li>
                            <li>Customer acquisition and retention metrics</li>
                            <li>Sales breakdown by store/branch</li>
                            <li>Payment methods distribution</li>
                            <li>Geographic sales analysis</li>
                            <li>Top performing products</li>
                            <li>Cart abandonment rates</li>
                            <li>Customer cohort analysis</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Estimación de tiempo -->
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <div class="flex">
                    <svg class="w-5 h-5 text-yellow-600 mr-3 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div class="text-sm text-yellow-800">
                        <p class="font-medium mb-1">Processing Time</p>
                        <p class="text-yellow-700">
                            This analysis will be processed in the background. 
                            Depending on the volume of orders, it may take from a few minutes to an hour.
                            You'll be notified when it's ready.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Botones -->
            <div class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-200">
                <a href="{{ route('sales.index') }}" 
                   class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                    Cancel
                </a>
                <button type="submit" 
                        class="px-6 py-3 bg-[#0244CD] text-white rounded-lg hover:bg-[#0244CD]/90 transition flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Start Analysis
                </button>
            </div>
        </div>
    </form>
</div>

<script>
function salesAnalysisForm() {
    return {
        periodType: '',
        startDate: '',
        endDate: '',
        
        updateDates() {
            // Auto-ajustar fechas según el tipo de periodo
            const today = new Date();
            const type = this.periodType;
            
            if (type === 'daily') {
                this.startDate = this.formatDate(new Date(today.getTime() - 24*60*60*1000));
                this.endDate = this.formatDate(new Date(today.getTime() - 24*60*60*1000));
            } else if (type === 'weekly') {
                const lastWeek = new Date(today.getTime() - 7*24*60*60*1000);
                this.startDate = this.getMonday(lastWeek);
                this.endDate = this.getSunday(lastWeek);
            } else if (type === 'monthly') {
                const lastMonth = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                this.startDate = this.formatDate(lastMonth);
                this.endDate = this.formatDate(new Date(today.getFullYear(), today.getMonth(), 0));
            }
        },
        
        setPreset(preset) {
            const today = new Date();
            
            switch(preset) {
                case 'last_week':
                    const lastWeek = new Date(today.getTime() - 7*24*60*60*1000);
                    this.startDate = this.getMonday(lastWeek);
                    this.endDate = this.getSunday(lastWeek);
                    this.periodType = 'weekly';
                    break;
                    
                case 'last_month':
                    const lastMonth = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                    this.startDate = this.formatDate(lastMonth);
                    this.endDate = this.formatDate(new Date(today.getFullYear(), today.getMonth(), 0));
                    this.periodType = 'monthly';
                    break;
                    
                case 'last_quarter':
                    const quarter = Math.floor(today.getMonth() / 3);
                    const lastQuarter = quarter === 0 ? 3 : quarter - 1;
                    const year = quarter === 0 ? today.getFullYear() - 1 : today.getFullYear();
                    this.startDate = this.formatDate(new Date(year, lastQuarter * 3, 1));
                    this.endDate = this.formatDate(new Date(year, (lastQuarter + 1) * 3, 0));
                    this.periodType = 'quarterly';
                    break;
                    
                case 'last_year':
                    this.startDate = this.formatDate(new Date(today.getFullYear() - 1, 0, 1));
                    this.endDate = this.formatDate(new Date(today.getFullYear() - 1, 11, 31));
                    this.periodType = 'yearly';
                    break;
            }
        },
        
        formatDate(date) {
            return date.toISOString().split('T')[0];
        },
        
        getMonday(date) {
            const d = new Date(date);
            const day = d.getDay();
            const diff = d.getDate() - day + (day === 0 ? -6 : 1);
            return this.formatDate(new Date(d.setDate(diff)));
        },
        
        getSunday(date) {
            const monday = new Date(this.getMonday(date));
            return this.formatDate(new Date(monday.getTime() + 6*24*60*60*1000));
        }
    }
}
</script>

<style>
[x-cloak] { display: none !important; }
</style>
@endsection
