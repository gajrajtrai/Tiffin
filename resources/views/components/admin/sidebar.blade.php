@php
    $user = auth()->user();

    // Nav structure: sections contain items. Empty sections are dropped.
    $sections = [
        [
            'title' => null,
            'items' => [
                ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'icon' => 'home', 'active' => request()->routeIs('admin.dashboard'), 'can' => 'order.view'],
            ],
        ],
        [
            'title' => 'Operations',
            'items' => [
                ['label' => 'Orders',   'route' => 'admin.orders.index',   'icon' => 'receipt',     'active' => request()->routeIs('admin.orders.*'),   'can' => 'order.view'],
                ['label' => 'Menu',     'route' => 'admin.menu.index',     'icon' => 'book',        'active' => request()->routeIs('admin.menu.*'),     'can' => 'menu.view'],
                ['label' => 'Payments', 'route' => 'admin.payments.index', 'icon' => 'credit-card', 'active' => request()->routeIs('admin.payments.*'), 'can' => 'payment.view'],
            ],
        ],
        [
            'title' => 'Back of House',
            'items' => [
                ['label' => 'Inventory', 'route' => 'admin.inventory.index', 'icon' => 'cube',  'active' => request()->routeIs('admin.inventory.*'), 'can' => 'inventory.view'],
                ['label' => 'Suppliers', 'route' => 'admin.suppliers.index', 'icon' => 'truck', 'active' => request()->routeIs('admin.suppliers.*'), 'can' => 'supplier.view'],
                ['label' => 'Purchases', 'route' => 'admin.purchases.index', 'icon' => 'truck', 'active' => request()->routeIs('admin.purchases.*'), 'can' => 'purchase.view'],
                ['label' => 'Receipts',  'route' => 'admin.receipts.index',  'icon' => 'truck', 'active' => request()->routeIs('admin.receipts.*'),  'can' => 'purchase.view'],
            ],
        ],
        [
            'title' => 'Insights',
            'items' => [
                ['label' => 'Reports',   'route' => 'admin.reports.index',  'icon' => 'chart-bar', 'active' => request()->routeIs('admin.reports.*'),  'can' => 'report.view'],
                ['label' => 'Expenses',  'route' => 'admin.expenses.index', 'icon' => 'banknotes', 'active' => request()->routeIs('admin.expenses.*'), 'can' => 'expense.view'],
                ['label' => 'Audit Log', 'route' => 'admin.audit.index',    'icon' => 'shield',    'active' => request()->routeIs('admin.audit.*'),    'can' => 'audit.view'],
            ],
        ],
        [
            'title' => 'Administration',
            'items' => [
                ['label' => 'Users',    'route' => 'admin.users.index',    'icon' => 'users', 'active' => request()->routeIs('admin.users.*'),    'can' => 'user.view'],
                ['label' => 'Settings', 'route' => 'admin.settings.index', 'icon' => 'cog',   'active' => request()->routeIs('admin.settings.*'), 'can' => 'settings.view'],
            ],
        ],
    ];

    $visibleSections = [];
    foreach ($sections as $section) {
        $items = array_values(array_filter($section['items'], function ($item) use ($user) {
            $can = $item['can'] ?? null;
            return ! $can || ($user && $user->can($can));
        }));

        if (empty($items)) {
            continue;
        }

        $visibleSections[] = [
            'title' => $section['title'],
            'items' => $items,
        ];
    }
@endphp

<aside x-cloak
       :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
       class="fixed inset-y-0 left-0 z-50 flex w-64 transform flex-col bg-slate-900 text-slate-200 transition-transform duration-200 lg:translate-x-0">

    {{-- Nav --}}
    <nav class="flex-1 overflow-y-auto px-3 py-4">
        @foreach ($visibleSections as $section)
            @if ($section['title'])
                <div class="mt-4 mb-1 px-3 text-[10px] font-semibold uppercase tracking-widest text-slate-500">
                    {{ $section['title'] }}
                </div>
            @endif

            @foreach ($section['items'] as $item)
                @php
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
            @endforeach
        @endforeach

        {{-- Bottom spacer so the last item isn't cramped --}}
        <div class="h-4"></div>
    </nav>
</aside>