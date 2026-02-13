<aside class="w-64 bg-[#0244CD] text-white flex flex-col" x-data="{ openMenu: '{{ request()->segment(1) ?? 'dashboard' }}' }">
    <div class="p-6 border-b border-white/10">
        <a href="{{ route('dashboard') }}" class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center">
                <span class="text-[#0244CD] font-bold text-xl">360</span>
            </div>
            <div>
                <h1 class="text-xl font-bold">360Sync</h1>
                <p class="text-xs text-white/70">Sync Operator</p>
            </div>
        </a>
    </div>

    <nav class="flex-1 px-4 py-6 space-y-1 overflow-y-auto">
        <a href="{{ route('dashboard') }}" 
           class="flex items-center px-4 py-3 rounded-lg transition {{ request()->routeIs('dashboard') ? 'bg-white/10 text-white' : 'text-white/70 hover:bg-white/5 hover:text-white' }}">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            <span class="font-medium">Dashboard</span>
        </a>

        <div x-data="{ open: openMenu === 'workflows' || openMenu === 'executions' || openMenu === 'schedule' }">
            <button @click="open = !open" 
                    class="w-full flex items-center justify-between px-4 py-3 rounded-lg transition text-white/70 hover:bg-white/5 hover:text-white">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    <span class="font-medium">Workflows</span>
                </div>
                <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            <div x-show="open" x-collapse class="ml-4 mt-1 space-y-1">
                <a href="{{ route('executions.index') }}" 
                   class="flex items-center px-4 py-2 rounded-lg text-sm transition {{ request()->routeIs('executions.*') ? 'bg-white/10 text-white' : 'text-white/70 hover:bg-white/5 hover:text-white' }}">
                    Executions
                </a>
                <a href="{{ route('schedule.index') }}" 
                   class="flex items-center px-4 py-2 rounded-lg text-sm transition {{ request()->routeIs('schedule.index') ? 'bg-white/10 text-white' : 'text-white/70 hover:bg-white/5 hover:text-white' }}">
                    Planning
                </a>
                <a href="{{ route('workflows.index') }}" 
                   class="flex items-center px-4 py-2 rounded-lg text-sm transition {{ request()->routeIs('workflows.*') ? 'bg-white/10 text-white' : 'text-white/70 hover:bg-white/5 hover:text-white' }}">
                    List
                </a>
            </div>
        </div>

        <div x-data="{ open: openMenu === 'analysis' }">
            <button @click="open = !open" 
                    class="w-full flex items-center justify-between px-4 py-3 rounded-lg transition {{ request()->routeIs('analysis.*') ? 'bg-white/10 text-white' : 'text-white/70 hover:bg-white/5 hover:text-white' }}">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                    <span class="font-medium">Product Analysis</span>
                </div>
                <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            <div x-show="open" x-collapse class="ml-4 mt-1 space-y-1">
                <a href="{{ route('analysis.index') }}" 
                   class="flex items-center px-4 py-2 rounded-lg text-sm transition {{ request()->routeIs('analysis.index') ? 'bg-white/10 text-white' : 'text-white/70 hover:bg-white/5 hover:text-white' }}">
                    Dashboard
                </a>
                <a href="{{ route('analysis.products') }}" 
                   class="flex items-center px-4 py-2 rounded-lg text-sm transition {{ request()->routeIs('analysis.products') || request()->routeIs('analysis.product-detail') ? 'bg-white/10 text-white' : 'text-white/70 hover:bg-white/5 hover:text-white' }}">
                    Products
                </a>
                <a href="{{ route('analysis.create') }}" 
                   class="flex items-center px-4 py-2 rounded-lg text-sm transition {{ request()->routeIs('analysis.create') ? 'bg-white/10 text-white' : 'text-white/70 hover:bg-white/5 hover:text-white' }}">
                    New Analysis
                </a>
                <a href="{{ route('analysis.configuration') }}" 
                   class="flex items-center px-4 py-2 rounded-lg text-sm transition {{ request()->routeIs('analysis.configuration') ? 'bg-white/10 text-white' : 'text-white/70 hover:bg-white/5 hover:text-white' }}">
                    Configuration
                </a>
            </div>
        </div>

        <div x-data="{ open: openMenu === 'sales' }">
            <button @click="open = !open" 
                    class="w-full flex items-center justify-between px-4 py-3 rounded-lg transition {{ request()->routeIs('sales.*') ? 'bg-white/10 text-white' : 'text-white/70 hover:bg-white/5 hover:text-white' }}">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                    <span class="font-medium">Sales Analysis</span>
                </div>
                <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            <div x-show="open" x-collapse class="ml-4 mt-1 space-y-1">
                <a href="{{ route('sales.executive-dashboard') }}" 
                   class="flex items-center px-4 py-2 rounded-lg text-sm transition {{ request()->routeIs('sales.executive-dashboard') ? 'bg-white/10 text-white' : 'text-white/70 hover:bg-white/5 hover:text-white' }}">
                    Executive View
                </a>
                <a href="{{ route('sales.dashboard') }}" 
                   class="flex items-center px-4 py-2 rounded-lg text-sm transition {{ request()->routeIs('sales.dashboard') ? 'bg-white/10 text-white' : 'text-white/70 hover:bg-white/5 hover:text-white' }}">
                    Technical Stats
                </a>
                <a href="{{ route('sales.by-store') }}" 
                   class="flex items-center px-4 py-2 rounded-lg text-sm transition {{ request()->routeIs('sales.by-store') ? 'bg-white/10 text-white' : 'text-white/70 hover:bg-white/5 hover:text-white' }}">
                    By Store
                </a>
                <a href="{{ route('sales.top-products') }}" 
                   class="flex items-center px-4 py-2 rounded-lg text-sm transition {{ request()->routeIs('sales.top-products') ? 'bg-white/10 text-white' : 'text-white/70 hover:bg-white/5 hover:text-white' }}">
                    Top Products
                </a>
            </div>
        </div>

        <div x-data="{ open: openMenu === 'configuration' || openMenu === 'categories' }">
            <button @click="open = !open" 
                    class="w-full flex items-center justify-between px-4 py-3 rounded-lg transition text-white/70 hover:bg-white/5 hover:text-white">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span class="font-medium">Settings</span>
                </div>
                <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            <div x-show="open" x-collapse class="ml-4 mt-1 space-y-1">
                <a href="{{ route('configuration.index') }}" 
                   class="flex items-center px-4 py-2 rounded-lg text-sm transition {{ request()->routeIs('configuration.*') ? 'bg-white/10 text-white' : 'text-white/70 hover:bg-white/5 hover:text-white' }}">
                    API Config
                </a>
                <a href="{{ route('categories.index') }}" 
                   class="flex items-center px-4 py-2 rounded-lg text-sm transition {{ request()->routeIs('categories.*') ? 'bg-white/10 text-white' : 'text-white/70 hover:bg-white/5 hover:text-white' }}">
                    Categories
                </a>
            </div>
        </div>

        <a href="{{ route('profile.edit') }}" 
           class="flex items-center px-4 py-3 rounded-lg transition {{ request()->routeIs('profile.*') ? 'bg-white/10 text-white' : 'text-white/70 hover:bg-white/5 hover:text-white' }}">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            <span class="font-medium">My Profile</span>
        </a>

        <a href="#" 
           class="flex items-center px-4 py-3 rounded-lg transition text-white/70 hover:bg-white/5 hover:text-white">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
            </svg>
            <span class="font-medium">User Guide</span>
        </a>
    </nav>

    <div class="p-4 border-t border-white/10">
        <p class="text-xs text-white/50 text-center">Powered by 360</p>
    </div>
</aside>