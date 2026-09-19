<div class="mx-auto max-w-5xl space-y-6">

    <a href="{{ route('admin.purchases.index') }}"
       class="inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-brand-600">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Back to Purchase Orders
    </a>

    <form wire:submit.prevent="saveDraft" class="space-y-6">

        {{-- Header --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-sm font-bold text-slate-900">Purchase Order Details</h2>

            <div class="space-y-4">
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Supplier <span class="text-rose-500">*</span></label>
                    <select wire:model="supplier_id"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                        <option value="">— Choose supplier —</option>
                        @foreach ($suppliers as $s)
                            <option value="{{ $s->id }}">{{ $s->name }}</option>
                        @endforeach
                    </select>
                    @error('supplier_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Order Date <span class="text-rose-500">*</span></label>
                        <input type="date" wire:model="order_date"
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                        @error('order_date') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Expected Delivery</label>
                        <input type="date" wire:model="expected_date"
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                        @error('expected_date') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Notes</label>
                    <textarea wire:model="notes" rows="2"
                              class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"></textarea>
                    @error('notes') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- Line items --}}
        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
                <h2 class="text-sm font-bold text-slate-900">Line Items</h2>
                <button type="button" wire:click="addItem"
                        class="inline-flex items-center gap-1 text-sm font-medium text-brand-600 hover:text-brand-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Add line
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Inventory Item</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Name</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 w-20">Unit</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 w-24">Qty</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 w-28">Unit Cost</th>
                            <th class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wider text-slate-500 w-24">Total</th>
                            <th class="w-10"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($items as $index => $line)
                            @php
                                $lineTotal = (float) ($line['quantity_ordered'] ?? 0) * (float) ($line['unit_cost'] ?? 0);
                            @endphp
                            <tr>
                                <td class="px-3 py-2">
                                    <select wire:model.live="items.{{ $index }}.inventory_item_id"
                                            class="w-full min-w-[180px] rounded-lg border border-slate-300 px-2 py-1.5 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                                        <option value="">— None / freeform —</option>
                                        @foreach ($inventoryItems as $inv)
                                            <option value="{{ $inv->id }}">{{ $inv->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-3 py-2">
                                    <input type="text" wire:model="items.{{ $index }}.item_name"
                                           placeholder="Item description"
                                           class="w-full min-w-[140px] rounded-lg border border-slate-300 px-2 py-1.5 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                                    @error("items.{$index}.item_name") <p class="mt-1 text-[10px] text-rose-600">{{ $message }}</p> @enderror
                                </td>
                                <td class="px-3 py-2">
                                    <input type="text" wire:model="items.{{ $index }}.unit"
                                           class="w-full rounded-lg border border-slate-300 px-2 py-1.5 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                                </td>
                                <td class="px-3 py-2">
                                    <input type="number" step="0.001" min="0.001"
                                           wire:model.live="items.{{ $index }}.quantity_ordered"
                                           class="w-full rounded-lg border border-slate-300 px-2 py-1.5 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                                    @error("items.{$index}.quantity_ordered") <p class="mt-1 text-[10px] text-rose-600">{{ $message }}</p> @enderror
                                </td>
                                <td class="px-3 py-2">
                                    <input type="number" step="0.01" min="0"
                                           wire:model.live="items.{{ $index }}.unit_cost"
                                           class="w-full rounded-lg border border-slate-300 px-2 py-1.5 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                                </td>
                                <td class="px-3 py-2 text-right font-semibold text-slate-800">
                                    Nu. {{ number_format($lineTotal, 2) }}
                                </td>
                                <td class="px-3 py-2 text-right">
                                    <button type="button" wire:click="removeItem({{ $index }})"
                                            class="rounded-md p-1 text-slate-400 hover:bg-rose-50 hover:text-rose-600"
                                            title="Remove line">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="border-t-2 border-slate-200 bg-slate-50">
                        <tr>
                            <td colspan="5" class="px-3 py-2 text-right text-sm font-semibold text-slate-700">Total</td>
                            <td class="px-3 py-2 text-right text-base font-bold text-slate-900">
                                Nu. {{ number_format($subtotal, 2) }}
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex flex-wrap items-center justify-end gap-3">
            <a href="{{ route('admin.purchases.index') }}"
               class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                Cancel
            </a>
            <button type="button" wire:click="saveDraft"
                    wire:loading.attr="disabled" wire:target="saveDraft"
                    class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-50">
                Save as Draft
            </button>
            <button type="button" wire:click="saveAndSend"
                    wire:loading.attr="disabled" wire:target="saveAndSend"
                    class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-600 disabled:opacity-50">
                <span wire:loading.remove wire:target="saveAndSend">Save & Send to Supplier</span>
                <span wire:loading wire:target="saveAndSend">Saving…</span>
            </button>
        </div>
    </form>
</div>