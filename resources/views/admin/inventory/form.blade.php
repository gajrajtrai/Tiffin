<div class="mx-auto max-w-3xl space-y-6">

    <a href="{{ route('admin.inventory.index') }}"
       class="inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-brand-600">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Back to Inventory
    </a>

    <form wire:submit="save" class="space-y-6">

        {{-- Basic info --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-sm font-bold text-slate-900">Item Details</h2>

            <div class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Name <span class="text-rose-500">*</span></label>
                        <input type="text" wire:model="name"
                               placeholder="e.g. Basmati Rice"
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                        @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">SKU (optional)</label>
                        <input type="text" wire:model="sku"
                               placeholder="e.g. RICE-001"
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                        @error('sku') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Category <span class="text-rose-500">*</span></label>
                        <select wire:model="category"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                            @foreach ($categories as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('category') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Unit <span class="text-rose-500">*</span></label>
                        <select wire:model="unit"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                            @foreach ($units as $u)
                                <option value="{{ $u }}">{{ $u }}</option>
                            @endforeach
                        </select>
                        @error('unit') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Stock + costs --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-sm font-bold text-slate-900">Stock & Cost</h2>

            @if ($item)
                <div class="mb-4 rounded-lg border border-sky-200 bg-sky-50 p-3 text-xs text-sky-800">
                    <strong>Note:</strong> Current stock is managed through the item's detail page
                    (Stock In / Out / Waste / Count). Editing here will not change stock.
                    Current: <strong>{{ $item->displayStock() }}</strong>
                </div>
            @endif

            <div class="grid gap-4 sm:grid-cols-3">
                @if (! $item)
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Opening Stock</label>
                        <input type="number" step="0.001" min="0"
                               wire:model="current_stock"
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                        <p class="mt-1 text-[11px] text-slate-400">Recorded as "Opening stock"</p>
                        @error('current_stock') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                @endif

                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Reorder Level</label>
                    <input type="number" step="0.001" min="0"
                           wire:model="reorder_level"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                    <p class="mt-1 text-[11px] text-slate-400">Alert when stock falls below this</p>
                    @error('reorder_level') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Unit Cost (Nu.)</label>
                    <input type="number" step="0.01" min="0"
                           wire:model="unit_cost"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                    <p class="mt-1 text-[11px] text-slate-400">Per unit (updated on stock-in)</p>
                    @error('unit_cost') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- Notes + active --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="space-y-4">
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Notes</label>
                    <textarea wire:model="notes" rows="3"
                              placeholder="Storage tips, supplier info, etc."
                              class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"></textarea>
                    @error('notes') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <label class="flex cursor-pointer items-center justify-between">
                    <div>
                        <div class="text-sm font-medium text-slate-800">Active</div>
                        <div class="text-xs text-slate-500">Inactive items are hidden from purchasing.</div>
                    </div>
                    <input type="checkbox" wire:model="is_active"
                           class="h-5 w-5 rounded border-slate-300 text-brand-500 focus:ring-brand-500" />
                </label>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('admin.inventory.index') }}"
               class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                Cancel
            </a>
            <button type="submit"
                    wire:loading.attr="disabled"
                    class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-600 disabled:opacity-50">
                <span wire:loading.remove wire:target="save">{{ $item ? 'Save changes' : 'Create item' }}</span>
                <span wire:loading wire:target="save">Saving…</span>
            </button>
        </div>
    </form>
</div>