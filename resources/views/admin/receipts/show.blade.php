@use('App\Modules\Supplier\Models\GoodsReceipt')

<div class="mx-auto max-w-5xl space-y-6">

    {{-- ─── Top bar ────────────────────────────────────────── --}}
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.receipts.index') }}"
           class="inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-brand-600">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Goods Receipts
        </a>

        @if ($gr->isDraft() && auth()->user()->can('purchase.edit'))
            <button type="button"
                    wire:click="deleteReceipt"
                    wire:confirm="Delete this draft receipt? This cannot be undone."
                    class="inline-flex items-center gap-1.5 rounded-lg border border-rose-300 bg-white px-3 py-1.5 text-sm font-medium text-rose-700 hover:bg-rose-50">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                Delete draft
            </button>
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
                    <h1 class="rounded-lg bg-sky-100 px-3 py-1 font-mono text-xl font-bold text-sky-700">
                        {{ $gr->display_ref }}
                    </h1>
                    <span class="font-mono text-xs text-slate-400">{{ $gr->receipt_number }}</span>
                    <x-admin.badge :variant="$gr->statusVariant()">{{ $gr->statusLabel() }}</x-admin.badge>
                </div>
                <div class="mt-2 text-sm text-slate-600">
                    Supplier: <strong>{{ $gr->supplier?->name ?? '—' }}</strong>
                </div>
                <div class="mt-1 text-xs text-slate-500">
                    Received {{ $gr->received_date->format('M j, Y') }}
                    @if ($gr->receivedBy)
                        · by {{ $gr->receivedBy->name }}
                    @endif
                    @if ($gr->purchaseOrder)
                        · from
                        <a href="{{ route('admin.purchases.show', $gr->purchaseOrder) }}"
                           class="rounded bg-brand-100 px-1.5 py-0.5 font-mono text-xs font-bold text-brand-700 hover:bg-brand-200">
                            {{ $gr->purchaseOrder->display_ref }}
                        </a>
                    @endif
                    @if ($gr->confirmed_at)
                        · Confirmed {{ $gr->confirmed_at->format('M j, H:i') }}
                    @endif
                </div>
                @if ($gr->notes)
                    <div class="mt-3 rounded-lg bg-slate-50 px-3 py-2 text-xs italic text-slate-600">
                        {{ $gr->notes }}
                    </div>
                @endif
            </div>

            @can('purchase.edit')
                <div class="w-full max-w-xs space-y-3">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Payment Method</label>
                        <select wire:model.live="paymentMethod"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                            <option value="cash">Cash</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="cheque">Cheque</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Payment Reference</label>
                        <input type="text" wire:model.live.debounce.500ms="paymentReference"
                               placeholder="optional"
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                    </div>
                    @if ($gr->isConfirmed() && $gr->expense)
                        <p class="text-[11px] text-slate-500">
                            Synced to expense
                            <span class="font-mono text-brand-600">{{ $gr->expense->expense_number }}</span>
                        </p>
                    @endif
                </div>
            @endcan
        </div>

        @if ($gr->isDraft())
            <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800">
                <strong>Draft:</strong> Adjust the quantities, unit costs, batches, and expiry as needed, then click
                <strong>Confirm Receipt</strong> to add the stock to inventory.
            </div>
        @endif
    </div>

    {{-- ─── Lines ──────────────────────────────────────────── --}}
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-4 py-3">
            <h2 class="text-sm font-bold text-slate-900">Received Items</h2>
        </div>

        @if ($gr->items->isEmpty())
            <div class="p-12 text-center">
                <p class="text-sm text-slate-500">No items on this receipt.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Item</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 w-28">Qty Received</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 w-28">Unit Cost</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 w-32">Batch</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 w-36">Expiry</th>
                            <th class="px-4 py-2 text-right text-xs font-semibold uppercase tracking-wider text-slate-500 w-24">Total</th>
                            @if ($gr->isDraft() && auth()->user()->can('purchase.edit'))
                                <th class="w-10"></th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($gr->items as $item)
                            @php
                                $line = $lines[$item->id] ?? [];
                                $lineTotal = (float) ($line['quantity_received'] ?? 0) * (float) ($line['unit_cost'] ?? 0);
                            @endphp
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-slate-800">{{ $item->item_name }}</div>
                                    @if ($item->inventoryItem)
                                        <div class="text-xs text-slate-400">→ {{ $item->inventoryItem->name }}</div>
                                    @endif
                                    @if ($item->purchaseOrderItem)
                                        <div class="text-[10px] text-slate-400">
                                            PO ordered: {{ rtrim(rtrim(number_format($item->purchaseOrderItem->quantity_ordered, 3), '0'), '.') }} {{ $item->unit }}
                                        </div>
                                    @endif
                                </td>

                                @if ($gr->isDraft() && auth()->user()->can('purchase.edit'))
                                    <td class="px-4 py-3">
                                        <input type="number" step="0.001" min="0"
                                               wire:model.live="lines.{{ $item->id }}.quantity_received"
                                               class="w-full rounded-lg border border-slate-300 px-2 py-1.5 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                                        <div class="mt-1 text-[10px] text-slate-400">{{ $item->unit }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <input type="number" step="0.01" min="0"
                                               wire:model.live="lines.{{ $item->id }}.unit_cost"
                                               class="w-full rounded-lg border border-slate-300 px-2 py-1.5 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                                    </td>
                                    <td class="px-4 py-3">
                                        <input type="text"
                                               wire:model="lines.{{ $item->id }}.batch_code"
                                               placeholder="optional"
                                               class="w-full rounded-lg border border-slate-300 px-2 py-1.5 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                                    </td>
                                    <td class="px-4 py-3">
                                        <input type="date"
                                               wire:model="lines.{{ $item->id }}.expiry_date"
                                               class="w-full rounded-lg border border-slate-300 px-2 py-1.5 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                                    </td>
                                @else
                                    <td class="px-4 py-3 text-slate-700">
                                        {{ rtrim(rtrim(number_format($item->quantity_received, 3), '0'), '.') }} {{ $item->unit }}
                                    </td>
                                    <td class="px-4 py-3 text-slate-700">
                                        Nu. {{ number_format($item->unit_cost, 2) }}
                                    </td>
                                    <td class="px-4 py-3 text-xs text-slate-600">
                                        {{ $item->batch_code ?: '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-xs text-slate-600">
                                        {{ $item->expiry_date?->format('M j, Y') ?? '—' }}
                                    </td>
                                @endif

                                <td class="px-4 py-3 text-right font-semibold text-slate-900">
                                    Nu. {{ number_format($lineTotal, 2) }}
                                </td>

                                @if ($gr->isDraft() && auth()->user()->can('purchase.edit'))
                                    <td class="px-4 py-3 text-right">
                                        <button type="button"
                                                wire:click="removeLine({{ $item->id }})"
                                                wire:confirm="Remove this line from the receipt?"
                                                class="rounded-md p-1 text-slate-400 hover:bg-rose-50 hover:text-rose-600"
                                                title="Remove line">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="border-t-2 border-slate-200 bg-slate-50">
                        <tr>
                            <td colspan="{{ $gr->isDraft() && auth()->user()->can('purchase.edit') ? 5 : 4 }}"
                                class="px-4 py-3 text-right text-sm font-semibold text-slate-700">Total</td>
                            <td class="px-4 py-3 text-right text-base font-bold text-slate-900">
                                Nu. {{ number_format($subtotal, 2) }}
                            </td>
                            @if ($gr->isDraft() && auth()->user()->can('purchase.edit'))
                                <td></td>
                            @endif
                        </tr>
                    </tfoot>
                </table>
            </div>

            {{-- Action bar --}}
            @if ($gr->isDraft() && auth()->user()->can('purchase.receive'))
                <div class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 bg-slate-50 px-4 py-3">
                    <button type="button" wire:click="saveChanges"
                            wire:loading.attr="disabled" wire:target="saveChanges"
                            class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 disabled:opacity-50">
                        Save changes
                    </button>
                    <button type="button" wire:click="confirm"
                            wire:confirm="Confirm receipt and add stock to inventory?"
                            wire:loading.attr="disabled" wire:target="confirm"
                            class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 disabled:opacity-50">
                        <svg wire:loading.remove wire:target="confirm" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <svg wire:loading wire:target="confirm" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        <span wire:loading.remove wire:target="confirm">Confirm Receipt</span>
                        <span wire:loading wire:target="confirm">Confirming…</span>
                    </button>
                </div>
            @elseif ($gr->isConfirmed())
                <div class="border-t border-slate-200 bg-emerald-50 px-4 py-3 text-xs text-emerald-800">
                    <strong>Confirmed.</strong> Stock has been added to inventory and the PO status updated.
                </div>
            @endif
        @endif
    </div>
</div>