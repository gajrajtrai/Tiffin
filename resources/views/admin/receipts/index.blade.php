<div class="space-y-6">

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Goods Receipts</h1>
            <p class="mt-1 text-sm text-slate-500">Deliveries received from suppliers, and their confirmation state.</p>
        </div>
    </div>

    <div class="grid grid-cols-3 gap-4">
        <x-admin.stat-card label="Total" :value="$counts['total']" icon="truck" color="brand" />
        <x-admin.stat-card label="Draft" :value="$counts['draft']" icon="truck" color="warning" />
        <x-admin.stat-card label="Confirmed" :value="$counts['confirmed']" icon="truck" color="emerald" />
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid gap-3 sm:grid-cols-12">
            <div class="sm:col-span-5">
                <label class="mb-1 block text-xs font-medium text-slate-600">Search</label>
                <input type="text" wire:model.live.debounce.300ms="search"
                       placeholder="Receipt #, PO #, or supplier"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
            </div>

            <div class="sm:col-span-3">
                <label class="mb-1 block text-xs font-medium text-slate-600">Status</label>
                <select wire:model.live="statusFilter"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                    <option value="all">All</option>
                    <option value="draft">Draft</option>
                    <option value="confirmed">Confirmed</option>
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

    @if ($receipts->isEmpty())
        <div class="rounded-xl border border-slate-200 bg-white p-12 text-center">
            <p class="text-sm text-slate-500">No goods receipts match the current filters.</p>
        </div>
    @else
        <x-admin.data-table :headers="['Receipt #', 'Supplier', 'PO', 'Received Date', 'Items', 'Total', 'Status', '']">
            @foreach ($receipts as $gr)
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3">
                        <div class="rounded bg-sky-100 px-1.5 py-0.5 font-mono text-xs font-bold text-sky-700 inline-block">
                            {{ $gr->display_ref }}
                        </div>
                        <div class="mt-0.5 font-mono text-[10px] text-slate-400">{{ $gr->receipt_number }}</div>
                    </td>
                    <td class="px-4 py-3 text-sm text-slate-800">
                        {{ $gr->supplier?->name ?? '—' }}
                    </td>
                    <td class="px-4 py-3 text-xs">
                        @if ($gr->purchaseOrder)
                            <a href="{{ route('admin.purchases.show', $gr->purchaseOrder) }}"
                               class="rounded bg-brand-100 px-1.5 py-0.5 font-mono text-xs font-bold text-brand-700 hover:bg-brand-200">
                                {{ $gr->purchaseOrder->display_ref }}
                            </a>
                        @else
                            <span class="text-slate-400">Walk-in</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-xs text-slate-600">
                        {{ $gr->received_date->format('M j, Y') }}
                    </td>
                    <td class="px-4 py-3 text-sm text-slate-700">
                        {{ $gr->items->count() }}
                    </td>
                    <td class="px-4 py-3 font-semibold text-slate-900">
                        Nu. {{ number_format($gr->total, 2) }}
                    </td>
                    <td class="px-4 py-3">
                        <x-admin.badge :variant="$gr->statusVariant()">{{ $gr->statusLabel() }}</x-admin.badge>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('admin.receipts.show', $gr) }}"
                           class="inline-flex items-center gap-1 text-sm font-medium text-brand-600 hover:text-brand-700">
                            {{ $gr->isDraft() ? 'Review' : 'View' }}
                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </td>
                </tr>
            @endforeach
        </x-admin.data-table>

        <div>{{ $receipts->links() }}</div>
    @endif
</div>