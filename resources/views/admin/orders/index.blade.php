<div class="space-y-6">

    {{-- ─── Page header ────────────────────────────────────── --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Orders</h1>
            <p class="mt-1 text-sm text-slate-500">
                Viewing orders for <strong>{{ $date->format('l, F j, Y') }}</strong>
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2 self-start sm:self-auto">
            <a href="{{ route('admin.orders.kitchen') }}"
               class="inline-flex items-center gap-2 rounded-lg border border-brand-500 bg-white px-4 py-2 text-sm font-semibold text-brand-600 hover:bg-brand-50">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                </svg>
                Kitchen Board
            </a>
            <button type="button" wire:click="goToday"
                    class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                Jump to today
            </button>
        </div>
    </div>

    {{-- ─── Summary cards ──────────────────────────────────── --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <x-admin.stat-card label="Total Orders" :value="$counts['total']" icon="receipt" color="brand" />
        <x-admin.stat-card label="Active" :value="$counts['active']" icon="cube" color="sky" />
        <x-admin.stat-card label="Ready" :value="$counts['ready']" icon="receipt" color="emerald" />
        <x-admin.stat-card label="Revenue" :value="'Nu. '.number_format($counts['revenue'], 0)" icon="banknotes" color="rose" />
    </div>

    {{-- ─── Filters ────────────────────────────────────────── --}}
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid gap-3 sm:grid-cols-12">

            <div class="sm:col-span-3">
                <label class="mb-1 block text-xs font-medium text-slate-600">Date</label>
                <input type="date" wire:model.live="dateFilter"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
            </div>

            <div class="sm:col-span-3">
                <label class="mb-1 block text-xs font-medium text-slate-600">Status</label>
                <select wire:model.live="statusFilter"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                    <option value="all">All statuses</option>
                    <option value="pending">Pending</option>
                    <option value="confirmed">Confirmed</option>
                    <option value="preparing">Preparing</option>
                    <option value="ready">Ready</option>
                    <option value="delivered">Delivered</option>
                    <option value="picked_up">Picked Up</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>

            <div class="sm:col-span-2">
                <label class="mb-1 block text-xs font-medium text-slate-600">Method</label>
                <select wire:model.live="methodFilter"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                    <option value="all">All</option>
                    <option value="delivery">Delivery</option>
                    <option value="pickup">Pickup</option>
                </select>
            </div>

            <div class="sm:col-span-3">
                <label class="mb-1 block text-xs font-medium text-slate-600">Search</label>
                <div class="relative">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/>
                    </svg>
                    <input type="text" wire:model.live.debounce.300ms="search"
                           placeholder="Order #, name, mobile"
                           class="w-full rounded-lg border border-slate-300 py-2 pl-9 pr-3 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                </div>
            </div>

            <div class="flex items-end sm:col-span-1">
                <button type="button" wire:click="clearFilters"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                        title="Clear filters">
                    ✕
                </button>
            </div>
        </div>
    </div>

    {{-- ─── Orders table ───────────────────────────────────── --}}
    @if ($orders->isEmpty())
        <div class="rounded-xl border border-slate-200 bg-white p-12 text-center">
            <p class="text-sm text-slate-500">No orders match the current filters.</p>
        </div>
    @else
        <x-admin.data-table :headers="['Order', 'Customer', 'Items', 'Method', 'Total', 'Status', '']">
            @foreach ($orders as $order)
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3">
                        <div class="font-mono text-xs font-semibold text-slate-700">{{ $order->order_number }}</div>
                        <div class="text-[10px] text-slate-400">{{ $order->created_at->format('H:i') }}</div>
                    </td>
                    <td class="px-4 py-3">
                        <div class="font-medium text-slate-900">{{ $order->user->name }}</div>
                        <div class="text-xs text-slate-500">{{ $order->user->mobile }}</div>
                    </td>
                    <td class="px-4 py-3">
                        <div class="text-xs text-slate-600">
                            @foreach ($order->items as $item)
                                <span class="{{ $item->is_veg ? 'text-emerald-600' : 'text-rose-600' }}">●</span>
                                {{ $item->item_name }}@if(!$loop->last)<span class="text-slate-400">, </span>@endif
                            @endforeach
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        @if ($order->isDelivery())
                            <span class="inline-flex items-center gap-1 text-xs text-slate-700">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1"/>
                                </svg>
                                Delivery
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 text-xs text-slate-700">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l9-9 9 9M5 10v10a1 1 0 001 1h3a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1h3a1 1 0 001-1V10"/>
                                </svg>
                                Pickup
                            </span>
                        @endif
                    </td>
                    <td class="px-4 py-3 font-semibold text-slate-900">
                        Nu. {{ number_format($order->total, 2) }}
                    </td>
                    <td class="px-4 py-3">
                        <x-admin.badge :variant="$order->statusVariant()">
                            {{ $order->statusLabel() }}
                        </x-admin.badge>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('admin.orders.show', $order) }}"
                           class="inline-flex items-center gap-1 text-sm font-medium text-brand-600 hover:text-brand-700">
                            View
                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </td>
                </tr>
            @endforeach
        </x-admin.data-table>

        <div>{{ $orders->links() }}</div>
    @endif
</div>