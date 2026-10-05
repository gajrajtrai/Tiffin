<div class="mx-auto max-w-3xl space-y-6">

    <a href="{{ route('admin.menu.index') }}"
       class="inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-brand-600">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Back to Menu
    </a>

    <form wire:submit="save" class="space-y-6">

        {{-- ─── Basic info ─────────────────────────────────── --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-sm font-bold text-slate-900">Item Details</h2>

            <div class="space-y-4">
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Name <span class="text-rose-500">*</span></label>
                    <input type="text" wire:model="name"
                           placeholder="e.g. Veg Thali"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                    @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Description (optional)</label>
                    <textarea wire:model="description" rows="3"
                              placeholder="Short description of the dish…"
                              class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"></textarea>
                    @error('description') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Type <span class="text-rose-500">*</span></label>
                        <select wire:model.live="type"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                            <option value="main">Main Course</option>
                            <option value="fastfood">Fast Food</option>
                        </select>
                        @error('type') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        @if ($type === 'main')
                            <p class="mt-1 text-[11px] text-amber-600">Only 2 main courses can be available on any given day.</p>
                        @endif
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Diet</label>
                        <div class="flex gap-2 pt-1">
                            <label class="flex flex-1 cursor-pointer items-center gap-2 rounded-lg border px-3 py-2 text-sm
                                          {{ $is_veg ? 'border-emerald-400 bg-emerald-50 text-emerald-700' : 'border-slate-200 text-slate-600' }}">
                                <input type="radio" wire:model.live="is_veg" value="1" class="text-emerald-500" />
                                Veg
                            </label>
                            <label class="flex flex-1 cursor-pointer items-center gap-2 rounded-lg border px-3 py-2 text-sm
                                          {{ ! $is_veg ? 'border-rose-400 bg-rose-50 text-rose-700' : 'border-slate-200 text-slate-600' }}">
                                <input type="radio" wire:model.live="is_veg" value="0" class="text-rose-500" />
                                Non-Veg
                            </label>
                        </div>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Price (Nu.) <span class="text-rose-500">*</span></label>
                        <input type="number" step="0.01" min="0" max="99999"
                               wire:model="price"
                               placeholder="120.00"
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                        @error('price') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Daily Limit</label>
                        <input type="number" min="1" max="9999"
                               wire:model="daily_limit"
                               placeholder="Unlimited"
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                        <p class="mt-1 text-[11px] text-slate-400">Max per day across all customers. Blank = no limit.</p>
                        @error('daily_limit') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Sort Order</label>
                        <input type="number" min="0" max="9999"
                               wire:model="sort_order"
                               placeholder="0"
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                        <p class="mt-1 text-[11px] text-slate-400">Lower numbers appear first</p>
                        @error('sort_order') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- ─── Image ──────────────────────────────────────── --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-sm font-bold text-slate-900">Photo</h2>

            <div class="flex items-start gap-4">
                {{-- Preview --}}
                <div class="shrink-0">
                    @if ($image)
                        <img src="{{ $image->temporaryUrl() }}"
                             class="h-32 w-32 rounded-xl object-cover ring-1 ring-slate-200">
                    @elseif ($item && $item->image_url && ! $remove_image)
                        <img src="{{ $item->image_url }}"
                             class="h-32 w-32 rounded-xl object-cover ring-1 ring-slate-200">
                    @else
                        <div class="flex h-32 w-32 items-center justify-center rounded-xl bg-slate-100 text-slate-400">
                            <svg class="h-10 w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                    @endif
                </div>

                <div class="flex-1 space-y-3">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Upload new photo</label>
                        <input type="file" wire:model="image" accept="image/*"
                               class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-100 file:px-4 file:py-2 file:text-sm file:font-medium file:text-brand-700 hover:file:bg-brand-200" />
                        @error('image') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        <p class="mt-1 text-[11px] text-slate-400">JPEG, PNG or WebP. Max 5 MB.</p>
                    </div>

                    <div wire:loading wire:target="image" class="text-xs text-brand-600">
                        Uploading…
                    </div>

                    @if ($item && $item->image_url && ! $remove_image)
                        <label class="flex items-center gap-2 text-xs text-slate-600">
                            <input type="checkbox" wire:model.live="remove_image" class="rounded border-slate-300 text-rose-500 focus:ring-rose-500" />
                            Remove existing photo
                        </label>
                    @endif
                </div>
            </div>
        </div>

        {{-- ─── Visibility ─────────────────────────────────── --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <label class="flex cursor-pointer items-center justify-between">
                <div>
                    <div class="text-sm font-medium text-slate-800">Active</div>
                    <div class="text-xs text-slate-500">Inactive items are hidden from the daily publisher.</div>
                </div>
                <input type="checkbox" wire:model="is_active"
                       class="h-5 w-5 rounded border-slate-300 text-brand-500 focus:ring-brand-500" />
            </label>
        </div>

        {{-- ─── Actions ────────────────────────────────────── --}}
        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('admin.menu.index') }}"
               class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                Cancel
            </a>
            <button type="submit"
                    wire:loading.attr="disabled"
                    class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-600 disabled:opacity-50">
                <svg wire:loading wire:target="save" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                <span wire:loading.remove wire:target="save">{{ $item ? 'Save changes' : 'Create item' }}</span>
                <span wire:loading wire:target="save">Saving…</span>
            </button>
        </div>
    </form>
</div>