@extends('layouts.app')

@section('title', 'Analysis Configuration')

@section('content')
<div>
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Analysis Configuration</h1>
                <p class="text-gray-600 mt-1">Configure parameters for product analysis</p>
            </div>
            <a href="{{ route('analysis.index') }}" 
               class="flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back
            </a>
        </div>
    </div>

    <form action="{{ route('analysis.update-configuration') }}" method="POST">
        @csrf

        @foreach($configurations as $category => $configs)
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4 capitalize">{{ str_replace('_', ' ', $category) }}</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach($configs as $config)
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        {{ $config->label }}
                        @if($config->is_required)
                        <span class="text-red-500">*</span>
                        @endif
                    </label>

                    @if($config->type === 'boolean')
                        <div class="flex items-center space-x-3">
                            <label class="inline-flex items-center">
                                <input type="radio" 
                                       name="configurations[{{ $config->key }}]" 
                                       value="true"
                                       {{ $config->parsed_value ? 'checked' : '' }}
                                       class="form-radio text-[#0244CD]">
                                <span class="ml-2 text-sm text-gray-700">Yes</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" 
                                       name="configurations[{{ $config->key }}]" 
                                       value="false"
                                       {{ !$config->parsed_value ? 'checked' : '' }}
                                       class="form-radio text-[#0244CD]">
                                <span class="ml-2 text-sm text-gray-700">No</span>
                            </label>
                        </div>

                    @elseif($config->type === 'integer')
                        <input type="number" 
                               name="configurations[{{ $config->key }}]" 
                               value="{{ $config->value }}"
                               {{ $config->is_required ? 'required' : '' }}
                               class="w-full border-gray-300 rounded-lg focus:ring-[#0244CD] focus:border-[#0244CD]">

                    @elseif($config->type === 'date')
                        <input type="date" 
                               name="configurations[{{ $config->key }}]" 
                               value="{{ $config->value }}"
                               {{ $config->is_required ? 'required' : '' }}
                               class="w-full border-gray-300 rounded-lg focus:ring-[#0244CD] focus:border-[#0244CD]">

                    @else
                        <input type="text" 
                               name="configurations[{{ $config->key }}]" 
                               value="{{ $config->value }}"
                               {{ $config->is_required ? 'required' : '' }}
                               class="w-full border-gray-300 rounded-lg focus:ring-[#0244CD] focus:border-[#0244CD]">
                    @endif

                    @if($config->description)
                    <p class="mt-1 text-xs text-gray-500">{{ $config->description }}</p>
                    @endif

                    @error('configurations.' . $config->key)
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                @endforeach
            </div>
        </div>
        @endforeach

        <!-- Actions -->
        <div class="flex items-center justify-between bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <p class="text-sm text-gray-600">
                Changes will affect future analysis executions
            </p>
            <div class="flex items-center space-x-3">
                <a href="{{ route('analysis.index') }}" 
                   class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                    Cancel
                </a>
                <button type="submit" 
                        class="px-6 py-3 bg-[#0244CD] text-white rounded-lg hover:bg-[#0244CD]/90 transition">
                    Save Configuration
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
