@extends('layouts.app')

@section('title', 'Analysis Execution Detail')

@section('content')
<div x-data="analysisDetail({{ $execution->id }})" x-init="init()">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <div class="flex items-center space-x-3">
                    <a href="{{ route('analysis.index') }}" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                    </a>
                    <h1 class="text-3xl font-bold text-gray-900">Analysis Execution</h1>
                </div>
                <p class="text-gray-600 mt-1">Job ID: {{ $execution->job_id }}</p>
            </div>

            <div class="flex items-center space-x-3">
                @if($execution->export_filename)
                <a href="{{ route('analysis.download', $execution->id) }}" 
                   class="flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Download Report
                </a>
                @endif

                @if($execution->status === 'completed')
                <a href="{{ route('analysis.products') }}?execution_id={{ $execution->id }}" 
                   class="flex items-center px-4 py-2 bg-[#0244CD] text-white rounded-lg hover:bg-[#0244CD]/90 transition">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                    View Products
                </a>
                @endif
            </div>
        </div>
    </div>

    <!-- Status Card -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <!-- Status Badge -->
                <div>
                    <span class="px-4 py-2 text-sm font-semibold rounded-full 
                        {{ $execution->status === 'completed' ? 'bg-green-100 text-green-800' : '' }}
                        {{ $execution->status === 'running' ? 'bg-blue-100 text-blue-800' : '' }}
                        {{ $execution->status === 'failed' ? 'bg-red-100 text-red-800' : '' }}
                        {{ $execution->status === 'pending' ? 'bg-gray-100 text-gray-800' : '' }}"
                        x-text="status.status_label">
                        {{ $execution->status_label }}
                    </span>
                </div>

                <div class="text-sm text-gray-600">
                    <span class="font-medium">Type:</span> 
                    <span class="text-gray-900">{{ $execution->analysis_type_label }}</span>
                </div>

                @if($execution->started_at)
                <div class="text-sm text-gray-600">
                    <span class="font-medium">Started:</span> 
                    <span class="text-gray-900">{{ $execution->started_at->format('M d, Y H:i') }}</span>
                </div>
                @endif

                @if($execution->completed_at)
                <div class="text-sm text-gray-600">
                    <span class="font-medium">Duration:</span> 
                    <span class="text-gray-900" x-text="status.duration">{{ $execution->formatted_duration }}</span>
                </div>
                @endif
            </div>

            @if($execution->status === 'running')
            <div class="flex items-center space-x-2 text-blue-600">
                <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-sm font-medium">Processing...</span>
            </div>
            @endif
        </div>
    </div>

    <!-- Progress Stats -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <!-- Total Products -->
        <div class="bg-white border border-gray-200 rounded-lg p-4">
            <div class="flex items-center justify-between mb-2">
                <svg class="w-8 h-8 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
            </div>
            <p class="text-2xl font-bold text-gray-900" x-text="status.progress.products_without_images.toLocaleString()">
                {{ number_format($execution->products_without_images) }}
            </p>
            <p class="text-xs text-gray-600 mt-1">Products Analyzed</p>
        </div>

        <!-- Matched -->
        <div class="bg-gradient-to-br from-green-50 to-green-100 border border-green-200 rounded-lg p-4">
            <div class="flex items-center justify-between mb-2">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <p class="text-2xl font-bold text-green-900" x-text="status.progress.products_matched.toLocaleString()">
                {{ number_format($execution->products_matched) }}
            </p>
            <p class="text-xs text-green-700 mt-1">
                Matched (<span x-text="status.progress.match_rate">{{ number_format($execution->match_rate, 1) }}</span>%)
            </p>
        </div>

        <!-- With Stock -->
        <div class="bg-gradient-to-br from-blue-50 to-blue-100 border border-blue-200 rounded-lg p-4">
            <div class="flex items-center justify-between mb-2">
                <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                </svg>
            </div>
            <p class="text-2xl font-bold text-blue-900" x-text="status.progress.products_with_stock.toLocaleString()">
                {{ number_format($execution->products_with_stock) }}
            </p>
            <p class="text-xs text-blue-700 mt-1">With Stock</p>
        </div>

        <!-- Meeting Criteria -->
        <div class="bg-gradient-to-br from-purple-50 to-purple-100 border border-purple-200 rounded-lg p-4">
            <div class="flex items-center justify-between mb-2">
                <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                </svg>
            </div>
            <p class="text-2xl font-bold text-purple-900" x-text="status.progress.products_meeting_criteria.toLocaleString()">
                {{ number_format($execution->products_meeting_criteria) }}
            </p>
            <p class="text-xs text-purple-700 mt-1">Meeting Criteria</p>
        </div>

        <!-- Errors -->
        <div class="bg-gradient-to-br from-red-50 to-red-100 border border-red-200 rounded-lg p-4">
            <div class="flex items-center justify-between mb-2">
                <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <p class="text-2xl font-bold text-red-900" x-text="status.progress.errors_count.toLocaleString()">
                {{ $execution->errors_count }}
            </p>
            <p class="text-xs text-red-700 mt-1">Errors</p>
        </div>
    </div>

    <!-- Execution Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="bg-white border border-gray-200 rounded-lg p-6">
            <h3 class="text-sm font-medium text-gray-600 mb-4">Review Status</h3>
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Pending</span>
                    <span class="font-semibold text-gray-900">{{ number_format($executionStats['pending_count']) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Reviewed</span>
                    <span class="font-semibold text-gray-900">{{ number_format($executionStats['reviewed_count']) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Approved</span>
                    <span class="font-semibold text-green-600">{{ number_format($executionStats['approved_count']) }}</span>
                </div>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-lg p-6">
            <h3 class="text-sm font-medium text-gray-600 mb-4">Match Quality</h3>
            <div class="space-y-3">
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-600">Match Rate</span>
                        <span class="font-semibold text-gray-900" x-text="status.progress.match_rate + '%'">{{ number_format($execution->match_rate, 1) }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-green-500 h-2 rounded-full" :style="`width: ${status.progress.match_rate}%`" style="width: {{ $execution->match_rate }}%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-600">Stock Rate</span>
                        <span class="font-semibold text-gray-900" x-text="status.progress.stock_rate + '%'">{{ number_format($execution->stock_rate, 1) }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-blue-500 h-2 rounded-full" :style="`width: ${status.progress.stock_rate}%`" style="width: {{ $execution->stock_rate }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-lg p-6">
            <h3 class="text-sm font-medium text-gray-600 mb-4">Execution Info</h3>
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">User</span>
                    <span class="font-semibold text-gray-900">{{ $execution->user->name }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Created</span>
                    <span class="font-semibold text-gray-900">{{ $execution->created_at->format('M d, Y H:i') }}</span>
                </div>
                @if($execution->result_message)
                <div class="pt-2 border-t border-gray-200">
                    <p class="text-xs text-gray-600">{{ $execution->result_message }}</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Execution Logs -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-bold text-gray-900">Execution Logs</h2>
                <div class="flex items-center space-x-2">
                    <select x-model="logFilter" @change="filterLogs()" 
                            class="text-sm border-gray-300 rounded-lg focus:ring-[#0244CD] focus:border-[#0244CD]">
                        <option value="">All Levels</option>
                        <option value="SUCCESS">Success</option>
                        <option value="INFO">Info</option>
                        <option value="WARNING">Warning</option>
                        <option value="ERROR">Error</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="p-6">
            <div class="space-y-2 max-h-96 overflow-y-auto">
                <template x-for="log in displayedLogs" :key="log.id">
                    <div class="flex items-start space-x-3 py-2 border-b border-gray-100 last:border-0">
                        <span class="text-xs text-gray-500 w-20 flex-shrink-0" x-text="log.time"></span>
                        <span class="px-2 py-1 text-xs font-semibold rounded"
                              :class="{
                                  'bg-green-100 text-green-800': log.level === 'SUCCESS',
                                  'bg-blue-100 text-blue-800': log.level === 'INFO',
                                  'bg-yellow-100 text-yellow-800': log.level === 'WARNING',
                                  'bg-red-100 text-red-800': log.level === 'ERROR'
                              }"
                              x-text="log.level"></span>
                        <p class="text-sm text-gray-700 flex-1" x-text="log.message"></p>
                        <span x-show="log.progress_percentage" 
                              class="text-xs text-gray-500 flex-shrink-0"
                              x-text="log.progress_percentage + '%'"></span>
                    </div>
                </template>
            </div>

            @if($execution->logs->isEmpty())
            <div class="text-center py-8 text-gray-500">
                <svg class="w-12 h-12 mx-auto text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <p>No logs available yet</p>
            </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
function analysisDetail(executionId) {
    return {
        executionId: executionId,
        status: {
            status: '{{ $execution->status }}',
            status_label: '{{ $execution->status_label }}',
            duration: '{{ $execution->formatted_duration }}',
            progress: {
                products_without_images: {{ $execution->products_without_images }},
                products_matched: {{ $execution->products_matched }},
                products_with_stock: {{ $execution->products_with_stock }},
                products_meeting_criteria: {{ $execution->products_meeting_criteria }},
                errors_count: {{ $execution->errors_count }},
                match_rate: {{ $execution->match_rate }},
                stock_rate: {{ $execution->stock_rate }}
            }
        },
        logs: [],
        displayedLogs: [],
        logFilter: '',
        
        init() {
            // Parse logs from PHP
            this.logs = {!! json_encode($execution->logs->map(function($log) {
                return [
                    'id' => $log->id,
                    'level' => $log->level,
                    'message' => $log->formatted_message,
                    'time' => $log->formatted_time,
                    'progress_percentage' => $log->progress_percentage,
                ];
            })->toArray()) !!};
            
            this.displayedLogs = this.logs;
            
            // Auto-refresh si está en progreso
            if (this.status.status === 'running') {
                setInterval(() => this.refreshStatus(), 5000);
            }
        },
        
        async refreshStatus() {
            try {
                const response = await fetch(`/analysis/${this.executionId}/status`);
                const data = await response.json();
                
                this.status = data;
                this.logs = data.logs;
                this.filterLogs();
                
                // Si terminó, recargar página
                if (data.status !== 'running') {
                    setTimeout(() => window.location.reload(), 2000);
                }
            } catch (error) {
                console.error('Error refreshing status:', error);
            }
        },
        
        filterLogs() {
            if (!this.logFilter) {
                this.displayedLogs = this.logs;
            } else {
                this.displayedLogs = this.logs.filter(log => log.level === this.logFilter);
            }
        }
    }
}
</script>
@endpush
@endsection