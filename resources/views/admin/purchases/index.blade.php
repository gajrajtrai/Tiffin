<div class="space-y-6">

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Purchase Orders</h1>
            <p class="mt-1 text-sm text-slate-500">Orders placed with suppliers.</p>
        </div>
        @can('purchase.create')
            <a href="{{ route('admin.purchases.create') }}"
               class="inline-flex items-center gap-2 self-start rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-600 sm:self-auto">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                New Purchase Order
            </a>
        @endcan
    </div>

    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <x-admin.stat-card label="Total" :value="$counts['total']" icon="truck" color="brand" />
        <x-admin.stat-card label="Draft" :value="$counts['draft']" icon="truck" color="slate" />
        <x-admin.stat-card label="Open" :value="$counts['open']" icon="truck" color="sky" />
        <x-admin.stat-card label="Received" :value="$counts['received']" icon="truck" color="emerald" />
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid gap-3 sm:grid-cols-12">
            <div class="sm:col-span-5">
                <label class="mb-1 block text-xs font-medium text-slate-600">Search</label>
                <input type="text" wire:model.live.debounce.300ms="search"
                       placeholder="PO number or supplier"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
            </div>

            <div class="sm:col-span-3">
                <label class="mb-1 block text-xs font-medium text-slate-600">Status</label>
                <select wire:model.live="statusFilter"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                    <option value="all">All</option>
                    <option value="draft">Draft</option>
                    <option value="sent">Sent</option>
                    <option value="partially_received">Partially received</option>
                    <option value="received">Received</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>

            <div class="sm:col-span-3">
                <label class="mb-1 block text-xs font-medium text-slate-600">Supplier</label>
                <select wire:model.live="supplierFilter"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                    <option value="">All suppliers</option>
                    @foreach ($suppliers as $s)
                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end sm:col-span-1">
                <button type="button" wire:click="clearFilters"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">✕</button>
            </div>
        </div>
    </div>

    @if ($orders->isEmpty())
        <div class="rounded-xl border border-slate-200 bg-white p-12 text-center">
            <p class="text-sm text-slate-500">No purchase orders match the current filters.</p>
        </div>
    @else
        <x-admin.data-table :headers="['PO Number', 'Supplier', 'Order Date', 'Items', 'Total', 'Status', '']">
            @foreach ($orders as $po)
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3 font-mono text-xs font-semibold text-slate-700">
                        {{ $po->po_number }}
                    </td>
                    <td class="px-4 py-3 text-sm text-slate-800">
                        {{ $po->supplier?->name ?? '—' }}
                    </td>
                    <td class="px-4 py-3 text-xs text-slate-600">
                        {{ $po->order_date?->format('M j, Y') }}
                        @if ($po->expected_date)
                            <div class="text-slate-400">Expected {{ $po->expected_date->format('M j') }}</div>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-sm text-slate-700">
                        {{ $po->items->count() }} line{{ $po->items->count() === 1 ? '' : 's' }}
                    </td>
                    <td class="px-4 py-3 font-semibold text-slate-900">
                        Nu. {{ number_format($po->total, 2) }}
                    </td>
                    <td class="px-4 py-3">
                        <x-admin.badge :variant="$po->statusVariant()">{{ $po->statusLabel() }}</x-admin.badge>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('admin.purchases.show', $po) }}"
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