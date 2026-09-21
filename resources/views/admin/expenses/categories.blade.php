<div class="mx-auto max-w-4xl space-y-6">

    {{-- ─── Header ─────────────────────────────────────────── --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a href="{{ route('admin.expenses.index') }}"
               class="inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-brand-600">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Back to Expenses
            </a>
            <h1 class="mt-2 text-xl font-bold text-slate-900">Expense Categories</h1>
            <p class="mt-1 text-sm text-slate-500">Organize expenses into categories for reporting.</p>
        </div>
        @can('expense.create')
            <button type="button" wire:click="openCreate"
                    class="inline-flex items-center gap-2 self-start rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-600 sm:self-auto">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add Category
            </button>
        @endcan
    </div>

    @if ($statusMessage)
        <x-admin.alert :type="$statusType">{{ $statusMessage }}</x-admin.alert>
    @endif

    {{-- ─── Table ──────────────────────────────────────────── --}}
    @if ($categories->isEmpty())
        <div class="rounded-xl border border-slate-200 bg-white p-12 text-center">
            <p class="text-sm text-slate-500">No categories yet. Add your first one to get started.</p>
        </div>
    @else
        <x-admin.data-table :headers="['Category', 'Description', 'Expenses', 'Total', 'Active', '']">
            @foreach ($categories as $cat)
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <x-admin.badge :variant="$cat->color ?: 'slate'">{{ $cat->name }}</x-admin.badge>
                            @if ($cat->slug === 'raw-materials')
                                <span class="rounded bg-slate-100 px-1.5 py-0.5 text-[9px] font-semibold uppercase tracking-wider text-slate-500" title="System category">
                                    System
                                </span>
                            @endif
                        </div>
                    </td>
                    <td class="px-4 py-3 text-xs text-slate-600">
                        {{ $cat->description ?: '—' }}
                    </td>
                    <td class="px-4 py-3 text-sm text-slate-700">
                        {{ $cat->expenses_count }}
                    </td>
                    <td class="px-4 py-3 font-semibold text-slate-900">
                        Nu. {{ number_format((float) ($cat->expenses_sum_amount ?? 0), 2) }}
                    </td>
                    <td class="px-4 py-3">
                        <x-admin.badge :variant="$cat->is_active ? 'success' : 'slate'">
                            {{ $cat->is_active ? 'Active' : 'Inactive' }}
                        </x-admin.badge>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex items-center justify-end gap-1">
                            @can('expense.edit')
                                <button type="button" wire:click="openEdit({{ $cat->id }})"
                                        class="rounded-md p-1.5 text-slate-500 hover:bg-slate-100 hover:text-brand-600"
                                        title="Edit">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </button>
                            @endcan

                            @if ($cat->slug !== 'raw-materials')
                                @can('expense.delete')
                                    <button type="button"
                                            wire:click="delete({{ $cat->id }})"
                                            wire:confirm="Delete category {{ $cat->name }}?"
                                            class="rounded-md p-1.5 text-slate-500 hover:bg-rose-50 hover:text-rose-600"
                                            title="Delete">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                @endcan
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-admin.data-table>
    @endif

    {{-- ─── Edit/Create modal ──────────────────────────────── --}}
    <x-admin.modal name="category-form" :title="$editingId ? 'Edit Category' : 'New Category'" maxWidth="md">
        @if ($showModal)
            <form wire:submit="save" class="space-y-4">
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Name <span class="text-rose-500">*</span></label>
                    <input type="text" wire:model="name"
                           placeholder="e.g. Raw Materials"
                           @if ($editingId && \App\Modules\Expense\Models\ExpenseCategory::find($editingId)?->slug === 'raw-materials') readonly @endif
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 read-only:bg-slate-100" />
                    @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Color</label>
                        <select wire:model="color"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                            <option value="slate">Gray</option>
                            <option value="brand">Orange (brand)</option>
                            <option value="success">Green</option>
                            <option value="warning">Amber</option>
                            <option value="danger">Red</option>
                            <option value="info">Blue</option>
                            <option value="sky">Sky</option>
                            <option value="emerald">Emerald</option>
                            <option value="rose">Rose</option>
                            <option value="amber">Amber</option>
                        </select>
                        @error('color') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Sort Order</label>
                        <input type="number" min="0" max="9999" wire:model="sort_order"
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                        @error('sort_order') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Description</label>
                    <textarea wire:model="description" rows="2"
                              placeholder="Optional — what counts as this category?"
                              class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"></textarea>
                    @error('description') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <label class="flex cursor-pointer items-center justify-between">
                    <div>
                        <div class="text-sm font-medium text-slate-800">Active</div>
                        <div class="text-xs text-slate-500">Inactive categories are hidden when recording new expenses.</div>
                    </div>
                    <input type="checkbox" wire:model="is_active"
                           class="h-5 w-5 rounded border-slate-300 text-brand-500 focus:ring-brand-500" />
                </label>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" wire:click="closeModal"
                            class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                        Cancel
                    </button>
                    <button type="submit"
                            wire:loading.attr="disabled" wire:target="save"
                            class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600 disabled:opacity-50">
                        <span wire:loading.remove wire:target="save">{{ $editingId ? 'Save changes' : 'Create category' }}</span>
                        <span wire:loading wire:target="save">Saving…</span>
                    </button>
                </div>
            </form>
        @endif
    </x-admin.modal>
</div>