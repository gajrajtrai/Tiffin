<div class="space-y-6">

    {{-- ─── Page header ────────────────────────────────────── --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Inventory</h1>
            <p class="mt-1 text-sm text-slate-500">Stock on hand, reorder levels, and current valuation.</p>
        </div>
        @can('inventory.adjust')
            <a href="{{ route('admin.inventory.create') }}"
               class="inline-flex items-center gap-2 self-start rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-600 sm:self-auto">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add Item
            </a>
        @endcan
    </div>

    {{-- ─── Stats ──────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <x-admin.stat-card label="Total Items" :value="$counts['total']" icon="cube" color="brand" />
        <x-admin.stat-card label="Low Stock" :value="$counts['lowStock']" icon="cube" color="rose" />
        <x-admin.stat-card label="Out of Stock" :value="$counts['outOfStock']" icon="cube" color="sky" />
        <x-admin.stat-card label="Stock Value" :value="'Nu. '.number_format($counts['totalValue'], 0)" icon="banknotes" color="emerald" />
    </div>

    {{-- ─── Filters ────────────────────────────────────────── --}}
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid gap-3 sm:grid-cols-12">

            <div class="sm:col-span-5">
                <label class="mb-1 block text-xs font-medium text-slate-600">Search</label>
                <div class="relative">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/>
                    </svg>
                    <input type="text" wire:model.live.debounce.300ms="search"
                           placeholder="Name or SKU"
                           class="w-full rounded-lg border border-slate-300 py-2 pl-9 pr-3 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                </div>
            </div>

            <div class="sm:col-span-3">
                <label class="mb-1 block text-xs font-medium text-slate-600">Category</label>
                <select wire:model.live="categoryFilter"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                    <option value="">All categories</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat }}">{{ ucfirst(str_replace('_', ' ', $cat)) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end sm:col-span-2">
                <button type="button" wire:click="toggleLowOnly"
                        class="w-full rounded-lg border px-3 py-2 text-sm font-medium transition
                               {{ $lowOnly === '1'
                                   ? 'border-rose-300 bg-rose-50 text-rose-700'
                                   : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50' }}">
                    {{ $lowOnly === '1' ? '✓ ' : '' }}Low stock only
                </button>
            </div>

            <div class="flex items-end sm:col-span-2">
                <button type="button" wire:click="clearFilters"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    Clear
                </button>
            </div>
        </div>
    </div>

    {{-- ─── Items table ────────────────────────────────────── --}}
    @if ($items->isEmpty())
        <div class="rounded-xl border border-slate-200 bg-white p-12 text-center">
            <p class="text-sm text-slate-500">No inventory items match the current filters.</p>
        </div>
    @else
        <x-admin.data-table :headers="['Item', 'Category', 'Stock', 'Reorder At', 'Unit Cost', 'Value', '']">
            @foreach ($items as $item)
                @php
                    $low = $item->isLowStock();
                    $out = $item->isOutOfStock();
                @endphp
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <span class="font-medium text-slate-900">{{ $item->name }}</span>
                            @if ($out)
                                <x-admin.badge variant="danger">Out</x-admin.badge>
                            @elseif ($low)
                                <x-admin.badge variant="warning">Low</x-admin.badge>
                            @endif
                        </div>
                        @if ($item->sku)
                            <div class="text-xs text-slate-400">SKU: {{ $item->sku }}</div>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-xs text-slate-600">
                        {{ ucfirst(str_replace('_', ' ', $item->category)) }}
                    </td>
                    <td class="px-4 py-3 font-semibold text-slate-900">
                        {{ $item->displayStock() }}
                    </td>
                    <td class="px-4 py-3 text-xs text-slate-500">
                        @if ((float) $item->reorder_level > 0)
                            {{ rtrim(rtrim(number_format($item->reorder_level, 3), '0'), '.') }} {{ $item->unit }}
                        @else
                            <span class="text-slate-300">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-sm text-slate-700">
                        Nu. {{ number_format($item->unit_cost, 2) }}
                    </td>
                    <td class="px-4 py-3 text-sm font-semibold text-slate-900">
                        Nu. {{ number_format($item->stockValue(), 0) }}
                    </td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('admin.inventory.show', $item) }}"
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

        <div>{{ $items->links() }}</div>
    @endif
</div>