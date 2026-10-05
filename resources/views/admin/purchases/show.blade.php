<div class="mx-auto max-w-4xl space-y-6">

    {{-- ─── Top bar ────────────────────────────────────────── --}}
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.purchases.index') }}"
           class="inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-brand-600">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Purchase Orders
        </a>

        @if ($po->isEditable() && auth()->user()->can('purchase.edit'))
            <a href="{{ route('admin.purchases.edit', $po) }}"
               class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Edit draft
            </a>
        @endif
    </div>

    @if ($statusMessage)
        <x-admin.alert :type="$statusType">{{ $statusMessage }}</x-admin.alert>
    @endif

    {{-- ─── Header ─────────────────────────────────────────── --}}
    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="rounded-lg bg-brand-100 px-3 py-1 font-mono text-xl font-bold text-brand-700">
                        {{ $po->display_ref }}
                    </h1>
                    <span class="font-mono text-xs text-slate-400">{{ $po->po_number }}</span>
                    <x-admin.badge :variant="$po->statusVariant()">{{ $po->statusLabel() }}</x-admin.badge>
                </div>
                <div class="mt-2 text-sm text-slate-600">
                    Supplier: <strong>{{ $po->supplier?->name ?? '—' }}</strong>
                </div>
                <div class="mt-1 text-xs text-slate-500">
                    Ordered {{ $po->order_date?->format('M j, Y') }}
                    @if ($po->expected_date)
                        · Expected {{ $po->expected_date->format('M j, Y') }}
                    @endif
                    @if ($po->sent_at)
                        · Sent {{ $po->sent_at->format('M j, H:i') }}
                    @endif
                </div>
                @if ($po->notes)
                    <div class="mt-3 rounded-lg bg-slate-50 px-3 py-2 text-xs italic text-slate-600">
                        {{ $po->notes }}
                    </div>
                @endif
            </div>

            {{-- Actions --}}
            <div class="flex flex-wrap items-start gap-2">
                @if ($po->isDraft() && auth()->user()->can('purchase.edit'))
                    <button type="button" wire:click="markSent"
                            class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-600">
                        Mark as sent
                    </button>
                @endif

                @if (in_array($po->status, [\App\Modules\Supplier\Models\PurchaseOrder::STATUS_SENT, \App\Modules\Supplier\Models\PurchaseOrder::STATUS_PARTIALLY_RECEIVED], true) && auth()->user()->can('purchase.receive'))
                    <button type="button" wire:click="createReceipt"
                            wire:loading.attr="disabled" wire:target="createReceipt"
                            class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-50">
                        <span wire:loading.remove wire:target="createReceipt">Receive goods</span>
                        <span wire:loading wire:target="createReceipt">Preparing…</span>
                    </button>
                @endif

                @if ($po->isCancellable() && auth()->user()->can('purchase.edit'))
                    <button type="button" x-data @click="$dispatch('open-modal-cancel-po')"
                            class="rounded-lg border border-rose-300 bg-white px-4 py-2 text-sm font-medium text-rose-700 hover:bg-rose-50">
                        Cancel
                    </button>
                @endif
            </div>
        </div>

        @if ($po->status === \App\Modules\Supplier\Models\PurchaseOrder::STATUS_CANCELLED)
            <div class="mt-4 rounded-lg border border-rose-200 bg-rose-50 p-3">
                <div class="text-xs font-semibold text-rose-800">
                    Cancelled {{ $po->cancelled_at?->format('M j, Y \a\t H:i') }}
                </div>
                @if ($po->cancelled_reason)
                    <div class="mt-1 text-sm text-rose-700">{{ $po->cancelled_reason }}</div>
                @endif
            </div>
        @endif
    </div>

    {{-- ─── Items ──────────────────────────────────────────── --}}
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-4 py-3">
            <h2 class="text-sm font-bold text-slate-900">Line Items</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b border-slate-200 bg-slate-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Item</th>
                        <th class="px-4 py-2 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Ordered</th>
                        <th class="px-4 py-2 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Received</th>
                        <th class="px-4 py-2 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Unit Cost</th>
                        <th class="px-4 py-2 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($po->items as $item)
                        <tr>
                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-800">{{ $item->item_name }}</div>
                                @if ($item->inventoryItem)
                                    <div class="text-xs text-slate-400">inventory: {{ $item->inventoryItem->name }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right text-slate-700">
                                {{ rtrim(rtrim(number_format($item->quantity_ordered, 3), '0'), '.') }} {{ $item->unit }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                @if ($item->isFullyReceived())
                                    <span class="font-semibold text-emerald-600">
                                        {{ rtrim(rtrim(number_format($item->quantity_received, 3), '0'), '.') }} ✓
                                    </span>
                                @elseif ($item->isPartiallyReceived())
                                    <span class="font-semibold text-amber-600">
                                        {{ rtrim(rtrim(number_format($item->quantity_received, 3), '0'), '.') }}
                                    </span>
                                @else
                                    <span class="text-slate-400">0</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right text-slate-700">
                                Nu. {{ number_format($item->unit_cost, 2) }}
                            </td>
                            <td class="px-4 py-3 text-right font-semibold text-slate-900">
                                Nu. {{ number_format($item->line_total, 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="border-t-2 border-slate-200 bg-slate-50">
                    <tr>
                        <td colspan="4" class="px-4 py-3 text-right text-sm font-semibold text-slate-700">Total</td>
                        <td class="px-4 py-3 text-right text-base font-bold text-slate-900">
                            Nu. {{ number_format($po->total, 2) }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- ─── Related Goods Receipts ─────────────────────────── --}}
    @if ($po->goodsReceipts->isNotEmpty())
        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-4 py-3">
                <h2 class="text-sm font-bold text-slate-900">Goods Receipts</h2>
            </div>
            <div class="divide-y divide-slate-100">
                @foreach ($po->goodsReceipts as $gr)
                    <div class="flex items-center justify-between px-4 py-3">
                        <div>
                            <a href="{{ route('admin.receipts.show', $gr) }}"
                               class="inline-flex items-center gap-2 rounded bg-sky-100 px-2 py-0.5 font-mono text-xs font-bold text-sky-700 hover:bg-sky-200">
                                {{ $gr->display_ref }}
                            </a>
                            <div class="text-xs text-slate-500">
                                {{ $gr->received_date->format('M j, Y') }} · {{ $gr->items->count() }} items
                            </div>
                        </div>
                        <div class="text-right">
                            <x-admin.badge :variant="$gr->statusVariant()">{{ $gr->statusLabel() }}</x-admin.badge>
                            <div class="mt-1 text-sm font-semibold text-slate-900">
                                Nu. {{ number_format($gr->total, 2) }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- ─── Cancel modal ───────────────────────────────────── --}}
    <x-admin.modal name="cancel-po" title="Cancel Purchase Order" maxWidth="md">
        <form wire:submit="cancel" class="space-y-4">
            <x-admin.alert type="warning">
                Cancelling this PO stops all further goods receipts against it.
            </x-admin.alert>

            <div>
                <label class="mb-1 block text-xs font-medium text-slate-600">
                    Reason <span class="text-rose-500">*</span>
                </label>
                <textarea wire:model="cancelReason" rows="3"
                          class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"></textarea>
                @error('cancelReason') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" x-data @click="$dispatch('close-modal-cancel-po')"
                        class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    Keep PO
                </button>
                <button type="submit"
                        class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-700">
                    Cancel PO
                </button>
            </div>
        </form>
    </x-admin.modal>
</div>