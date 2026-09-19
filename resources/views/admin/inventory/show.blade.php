<div class="mx-auto max-w-4xl space-y-6">

    {{-- ─── Top bar ────────────────────────────────────────── --}}
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.inventory.index') }}"
           class="inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-brand-600">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Inventory
        </a>

        @can('inventory.adjust')
            <a href="{{ route('admin.inventory.edit', $item) }}"
               class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Edit details
            </a>
        @endcan
    </div>

    @if ($statusMessage)
        <x-admin.alert :type="$statusType">{{ $statusMessage }}</x-admin.alert>
    @endif

    {{-- ─── Header card ─────────────────────────────────────── --}}
    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-xl font-bold text-slate-900">{{ $item->name }}</h1>
                    @if (! $item->is_active)
                        <x-admin.badge variant="slate">Inactive</x-admin.badge>
                    @endif
                    @if ($stats['isOut'])
                        <x-admin.badge variant="danger">Out of stock</x-admin.badge>
                    @elseif ($stats['isLow'])
                        <x-admin.badge variant="warning">Low stock</x-admin.badge>
                    @endif
                </div>
                <div class="mt-2 text-sm text-slate-600">
                    <span class="font-medium">{{ ucfirst(str_replace('_', ' ', $item->category)) }}</span>
                    @if ($item->sku) · <span class="font-mono text-xs">SKU {{ $item->sku }}</span> @endif
                </div>
                @if ($item->notes)
                    <div class="mt-3 max-w-xl text-sm italic text-slate-500">"{{ $item->notes }}"</div>
                @endif
            </div>
        </div>
    </div>

    {{-- ─── Stock summary ──────────────────────────────────── --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-medium uppercase tracking-wider text-slate-500">Current Stock</div>
            <div class="mt-1 text-2xl font-bold {{ $stats['isOut'] ? 'text-rose-600' : ($stats['isLow'] ? 'text-amber-600' : 'text-slate-900') }}">
                {{ $item->displayStock() }}
            </div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-medium uppercase tracking-wider text-slate-500">Reorder At</div>
            <div class="mt-1 text-2xl font-bold text-slate-900">
                @if ($stats['reorder'] > 0)
                    {{ rtrim(rtrim(number_format($stats['reorder'], 3), '0'), '.') }} <span class="text-base font-normal text-slate-400">{{ $item->unit }}</span>
                @else
                    <span class="text-base font-normal text-slate-400">—</span>
                @endif
            </div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-medium uppercase tracking-wider text-slate-500">Unit Cost</div>
            <div class="mt-1 text-2xl font-bold text-slate-900">
                Nu. {{ number_format($item->unit_cost, 2) }}
            </div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-medium uppercase tracking-wider text-slate-500">Stock Value</div>
            <div class="mt-1 text-2xl font-bold text-brand-600">
                Nu. {{ number_format($stats['stockValue'], 0) }}
            </div>
        </div>
    </div>

    {{-- ─── Adjustment actions ─────────────────────────────── --}}
    @can('inventory.adjust')
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-sm font-semibold text-slate-700">Adjust stock:</div>
            <div class="mt-3 flex flex-wrap gap-2">
                <button type="button" x-data @click="$dispatch('open-modal-stock-in')"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-emerald-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Stock In
                </button>

                <button type="button" x-data @click="$dispatch('open-modal-stock-out')"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-sky-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-sky-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                    </svg>
                    Stock Out
                </button>

                <button type="button" x-data @click="$dispatch('open-modal-stock-waste')"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-rose-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-rose-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Record Waste
                </button>

                <button type="button" x-data @click="$dispatch('open-modal-stock-count')"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-amber-300 bg-amber-50 px-3 py-1.5 text-sm font-medium text-amber-800 hover:bg-amber-100">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    Physical Count
                </button>
            </div>
        </div>
    @endcan

    {{-- ─── Movement history ───────────────────────────────── --}}
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-4 py-3">
            <h3 class="text-sm font-bold text-slate-900">Movement History</h3>
            <p class="mt-0.5 text-xs text-slate-500">Last 50 movements</p>
        </div>

        @if ($movements->isEmpty())
            <div class="p-8 text-center">
                <p class="text-sm text-slate-500">No stock movements yet.</p>
            </div>
        @else
            <div class="divide-y divide-slate-100">
                @foreach ($movements as $m)
                    <div class="flex items-center gap-3 px-4 py-3">
                        <div class="shrink-0">
                            <x-admin.badge :variant="$m->typeVariant()">{{ $m->typeLabel() }}</x-admin.badge>
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <span class="font-mono text-sm font-semibold {{ $m->quantity >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                    {{ $m->signed_quantity }} {{ $item->unit }}
                                </span>
                                <span class="text-xs text-slate-400">
                                    bal {{ rtrim(rtrim(number_format($m->balance_after, 3), '0'), '.') }}
                                </span>
                            </div>
                            <div class="mt-0.5 text-xs text-slate-600">{{ $m->reason ?: '—' }}</div>
                            @if ($m->notes)
                                <div class="text-xs italic text-slate-500">{{ $m->notes }}</div>
                            @endif
                        </div>

                        <div class="shrink-0 text-right text-xs text-slate-400">
                            <div>{{ $m->created_at->format('M j, H:i') }}</div>
                            @if ($m->createdBy)
                                <div>{{ $m->createdBy->name }}</div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- ─── Modals ─────────────────────────────────────────── --}}

    {{-- Stock In --}}
    <x-admin.modal name="stock-in" title="Add Stock" maxWidth="md">
        <form wire:submit="stockIn" class="space-y-4">
            <x-admin.alert type="success">
                Records a purchase or delivery. Updates current stock and unit cost.
            </x-admin.alert>

            <div>
                <label class="mb-1 block text-xs font-medium text-slate-600">
                    Quantity received ({{ $item->unit }}) <span class="text-rose-500">*</span>
                </label>
                <input type="number" step="0.001" min="0.001"
                       wire:model="stockInQuantity"
                       placeholder="e.g. 25"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                @error('stockInQuantity') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-slate-600">
                    Unit cost (Nu. per {{ $item->unit }}) <span class="text-rose-500">*</span>
                </label>
                <input type="number" step="0.01" min="0"
                       wire:model="stockInCost"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                <p class="mt-1 text-[11px] text-slate-400">Updates the item's unit cost to this value</p>
                @error('stockInCost') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-slate-600">
                    Reason <span class="text-rose-500">*</span>
                </label>
                <input type="text" wire:model="stockInReason"
                       placeholder="e.g. Weekly vegetable delivery"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                @error('stockInReason') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" x-data @click="$dispatch('close-modal-stock-in')"
                        class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    Cancel
                </button>
                <button type="submit"
                        class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                    Add stock
                </button>
            </div>
        </form>
    </x-admin.modal>

    {{-- Stock Out --}}
    <x-admin.modal name="stock-out" title="Record Usage" maxWidth="md">
        <form wire:submit="stockOut" class="space-y-4">
            <x-admin.alert type="info">
                Records consumption (kitchen use). Cannot take stock below zero.
            </x-admin.alert>

            <div>
                <label class="mb-1 block text-xs font-medium text-slate-600">
                    Quantity used ({{ $item->unit }}) <span class="text-rose-500">*</span>
                </label>
                <input type="number" step="0.001" min="0.001"
                       wire:model="stockOutQuantity"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                @error('stockOutQuantity') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-slate-600">
                    Reason <span class="text-rose-500">*</span>
                </label>
                <input type="text" wire:model="stockOutReason"
                       placeholder="e.g. Lunch prep — 15 tiffins"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                @error('stockOutReason') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" x-data @click="$dispatch('close-modal-stock-out')"
                        class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    Cancel
                </button>
                <button type="submit"
                        class="rounded-lg bg-sky-600 px-4 py-2 text-sm font-medium text-white hover:bg-sky-700">
                    Record usage
                </button>
            </div>
        </form>
    </x-admin.modal>

    {{-- Waste --}}
    <x-admin.modal name="stock-waste" title="Record Waste" maxWidth="md">
        <form wire:submit="recordWaste" class="space-y-4">
            <x-admin.alert type="warning">
                Records spoilage, expiry, or damage. Tracked separately from normal usage.
            </x-admin.alert>

            <div>
                <label class="mb-1 block text-xs font-medium text-slate-600">
                    Quantity wasted ({{ $item->unit }}) <span class="text-rose-500">*</span>
                </label>
                <input type="number" step="0.001" min="0.001"
                       wire:model="wasteQuantity"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                @error('wasteQuantity') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-slate-600">
                    Reason <span class="text-rose-500">*</span>
                </label>
                <input type="text" wire:model="wasteReason"
                       placeholder="e.g. Spoiled — moisture damage"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                @error('wasteReason') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" x-data @click="$dispatch('close-modal-stock-waste')"
                        class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    Cancel
                </button>
                <button type="submit"
                        class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-700">
                    Record waste
                </button>
            </div>
        </form>
    </x-admin.modal>

    {{-- Physical count --}}
    <x-admin.modal name="stock-count" title="Physical Count Adjustment" maxWidth="md">
        <form wire:submit="adjustStock" class="space-y-4">
            <x-admin.alert type="info">
                Enter the <strong>actual physical count</strong>. The system records the difference as an adjustment.
                Current system balance: <strong>{{ $item->displayStock() }}</strong>
            </x-admin.alert>

            <div>
                <label class="mb-1 block text-xs font-medium text-slate-600">
                    Physical count ({{ $item->unit }}) <span class="text-rose-500">*</span>
                </label>
                <input type="number" step="0.001" min="0"
                       wire:model="countQuantity"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                @error('countQuantity') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-slate-600">
                    Reason <span class="text-rose-500">*</span>
                </label>
                <input type="text" wire:model="countReason"
                       placeholder="e.g. Weekly stock take"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                @error('countReason') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" x-data @click="$dispatch('close-modal-stock-count')"
                        class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    Cancel
                </button>
                <button type="submit"
                        class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-medium text-white hover:bg-amber-700">
                    Apply adjustment
                </button>
            </div>
        </form>
    </x-admin.modal>
</div>