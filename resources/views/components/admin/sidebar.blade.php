@php
    $user = auth()->user();
    $nav = [
        ['label' => 'Dashboard',   'route' => 'admin.dashboard',    'icon' => 'home',      'active' => request()->routeIs('admin.dashboard')],
        ['section' => 'Operations'],
        ['label' => 'Orders',      'route' => 'admin.orders.index', 'icon' => 'receipt',   'active' => request()->routeIs('admin.orders.*')],
		['label' => 'Kitchen Board', 'route' => 'admin.orders.kitchen', 'icon' => 'cube', 'active' => request()->routeIs('admin.orders.kitchen')],
        ['label' => 'Menu',        'route' => 'admin.menu.index',   'icon' => 'book',      'active' => request()->routeIs('admin.menu.*')],
        ['label' => 'Payments',    'route' => 'admin.payments.index', 'icon' => 'credit-card', 'active' => request()->routeIs('admin.payments.*')],
        ['section' => 'Back of House'],
        ['label' => 'Inventory',   'route' => 'admin.inventory.index', 'icon' => 'cube',    'active' => request()->routeIs('admin.inventory.*')],
        ['label' => 'Suppliers',   'route' => 'admin.suppliers.index', 'icon' => 'truck',   'active' => request()->routeIs('admin.suppliers.*')],
        ['label' => 'Expenses',    'route' => 'admin.expenses.index',  'icon' => 'banknotes', 'active' => request()->routeIs('admin.expenses.*')],
        ['section' => 'Insights'],
        ['label' => 'Reports',     'route' => 'admin.reports.index', 'icon' => 'chart-bar', 'active' => request()->routeIs('admin.reports.*')],
        ['label' => 'Audit Log',   'route' => 'admin.audit.index',   'icon' => 'shield',    'active' => request()->routeIs('admin.audit.*'), 'can' => 'audit.view'],
        ['section' => 'Administration'],
        ['label' => 'Users',       'route' => 'admin.users.index',   'icon' => 'users',     'active' => request()->routeIs('admin.users.*'), 'can' => 'user.view'],
        ['label' => 'Settings',    'route' => 'admin.settings.index','icon' => 'cog',       'active' => request()->routeIs('admin.settings.*'), 'can' => 'settings.view'],
    ];
@endphp
<aside x-cloak :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
       class="fixed inset-y-0 left-0 z-50 w-64 transform bg-slate-900 text-slate-200 transition-transform duration-200 lg:translate-x-0">
    <div class="flex h-16 items-center gap-2 border-b border-slate-800 px-5">
        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-500 text-white">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
            </svg>
        </div>
        <div class="leading-tight">
            <div class="text-sm font-bold text-white">{{ config('app.name') }}</div>
            <div class="text-[10px] uppercase tracking-widest text-slate-400">Admin</div>
        </div>
    </div>
    <nav class="flex-1 overflow-y-auto px-3 py-4">
        @foreach ($nav as $item)
            @if (isset($item['section']))
                <div class="mt-4 mb-1 px-3 text-[10px] font-semibold uppercase tracking-widest text-slate-500">{{ $item['section'] }}</div>
            @else
                @php
                    $can = $item['can'] ?? null;
                    if ($can && (! $user || ! $user->can($can))) continue;
                    $routeExists = Route::has($item['route']);
                    $href = $routeExists ? route($item['route']) : '#';
                    $isActive = $routeExists && $item['active'];
                @endphp
                <a href="{{ $href }}"
                   @class([
                       'group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition',
                       'bg-brand-500 text-white shadow-sm' => $isActive,
                       'text-slate-300 hover:bg-slate-800 hover:text-white' => ! $isActive && $routeExists,
                       'text-slate-500 cursor-not-allowed' => ! $routeExists,
                   ])
                   @if (! $routeExists) title="Coming soon" @endif>
                    <x-admin.icon :name="$item['icon']" class="h-4 w-4 shrink-0" />
                    <span>{{ $item['label'] }}</span>
                    @if (! $routeExists)
                        <span class="ml-auto rounded bg-slate-800 px-1.5 py-0.5 text-[9px] uppercase text-slate-400">soon</span>
                    @endif
                </a>
            @endif
        @endforeach
    </nav>
    <div class="border-t border-slate-800 px-5 py-3 text-[10px] text-slate-500">v0.1 · Phase 3</div>
</aside>