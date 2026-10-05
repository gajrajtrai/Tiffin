<div class="space-y-6">

    {{-- ─── Header ─────────────────────────────────────────── --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Expenses</h1>
            <p class="mt-1 text-sm text-slate-500">
                From <strong>{{ $fromDate->format('M j, Y') }}</strong> to <strong>{{ $toDate->format('M j, Y') }}</strong>
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2 self-start sm:self-auto">
            @can('expense.create')
                <a href="{{ route('admin.expenses.categories') }}"
                   class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5a1.99 1.99 0 011.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z"/>
                    </svg>
                    Categories
                </a>
                <a href="{{ route('admin.expenses.create') }}"
                   class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-600">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    New Expense
                </a>
            @endcan
        </div>
    </div>

    @if ($statusMessage)
        <x-admin.alert :type="$statusType">{{ $statusMessage }}</x-admin.alert>
    @endif

    {{-- ─── KPI cards ──────────────────────────────────────── --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-5">
        <x-admin.stat-card label="Total in Range" :value="'Nu. '.number_format($stats['total'], 0)" icon="banknotes" color="brand" />
        <x-admin.stat-card label="Entries" :value="$stats['count']" icon="receipt" color="sky" />
        <x-admin.stat-card label="Categories Used" :value="$stats['categoriesUsed']" icon="book" color="emerald" />
        <x-admin.stat-card label="Missing Receipt" :value="$stats['withoutReceipt']" icon="receipt" color="rose" />
        <x-admin.stat-card label="Voided" :value="$stats['voidedCount']" icon="receipt" color="slate" />
    </div>

    {{-- ─── Filters ────────────────────────────────────────── --}}
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid gap-3 sm:grid-cols-12">

            <div class="sm:col-span-4">
                <label class="mb-1 block text-xs font-medium text-slate-600">Search</label>
                <div class="relative">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/>
                    </svg>
                    <input type="text" wire:model.live.debounce.300ms="search"
                           placeholder="Description, expense #, or reference"
                           class="w-full rounded-lg border border-slate-300 py-2 pl-9 pr-3 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                </div>
            </div>

            <div class="sm:col-span-3">
                <label class="mb-1 block text-xs font-medium text-slate-600">Category</label>
                <select wire:model.live="categoryFilter"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                    <option value="">All categories</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="sm:col-span-2">
                <label class="mb-1 block text-xs font-medium text-slate-600">Show</label>
                <select wire:model.live="showFilter"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                    <option value="active">Active</option>
                    <option value="voided">Voided only</option>
                    <option value="all">All</option>
                </select>
            </div>

            <div class="sm:col-span-2">
                <label class="mb-1 block text-xs font-medium text-slate-600">From</label>
                <input type="date" wire:model.live="from"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
            </div>

            <div class="sm:col-span-1 flex items-end">
                <button type="button" wire:click="clearFilters"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">✕</button>
            </div>

            <div class="sm:col-span-2">
                <label class="mb-1 block text-xs font-medium text-slate-600">To</label>
                <input type="date" wire:model.live="to"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
            </div>
        </div>

        {{-- Presets --}}
        <div class="mt-3 flex flex-wrap items-center gap-2">
            <span class="text-[11px] font-medium uppercase tracking-wider text-slate-400">Quick range:</span>
            <button type="button" wire:click="presetToday"
                    class="rounded-md border border-slate-300 bg-white px-2.5 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50">
                Today
            </button>
            <button type="button" wire:click="presetThisMonth"
                    class="rounded-md border border-slate-300 bg-white px-2.5 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50">
                This month
            </button>
            <button type="button" wire:click="presetLastMonth"
                    class="rounded-md border border-slate-300 bg-white px-2.5 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50">
                Last month
            </button>
        </div>
    </div>

    {{-- ─── Two-column: Table + Sidebar breakdown ─────────── --}}
    <div class="grid gap-6 lg:grid-cols-3">

        {{-- Table --}}
        <div class="lg:col-span-2">
            @if ($expenses->isEmpty())
                <div class="rounded-xl border border-slate-200 bg-white p-12 text-center">
                    <p class="text-sm text-slate-500">No expenses match the current filters.</p>
                </div>
            @else
                <x-admin.data-table :headers="['Expense #', 'Date', 'Category', 'Description', 'Amount', '']">
                    @foreach ($expenses as $expense)
                        <tr class="hover:bg-slate-50 {{ $expense->isVoided() ? 'opacity-60' : '' }}">
                            <td class="px-4 py-3">
                                <div class="font-mono text-xs text-slate-500">{{ $expense->expense_number }}</div>
                                <div class="mt-0.5 flex flex-wrap items-center gap-1">
                                    @if ($expense->isGrLinked())
                                        <a href="{{ route('admin.receipts.show', $expense->goodsReceipt) }}"
                                           class="inline-flex items-center gap-1 rounded bg-sky-100 px-1.5 py-0.5 text-[9px] font-semibold uppercase tracking-wider text-sky-700 hover:bg-sky-200">
                                           via {{ $expense->goodsReceipt->display_ref }}
                                        </a>
                                    @endif
                                    @if ($expense->hasMedia(\App\Modules\Expense\Models\Expense::MEDIA_RECEIPT))
                                        <a href="{{ $expense->getFirstMediaUrl(\App\Modules\Expense\Models\Expense::MEDIA_RECEIPT) }}"
                                           target="_blank"
                                           class="inline-flex items-center gap-1 text-[10px] font-medium uppercase text-emerald-600 hover:text-emerald-700">
                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                                            </svg>
                                            Receipt
                                        </a>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-600">
                                {{ $expense->expense_date->format('M j, Y') }}
                            </td>
                            <td class="px-4 py-3">
                                @if ($expense->category)
                                    <x-admin.badge :variant="$expense->category->color ?: 'slate'">
                                        {{ $expense->category->name }}
                                    </x-admin.badge>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm text-slate-800">{{ $expense->description }}</span>
                                    @if ($expense->isVoided())
                                        <x-admin.badge variant="danger">Voided</x-admin.badge>
                                    @endif
                                </div>
                                <div class="mt-0.5 text-xs text-slate-500">
                                    {{ $expense->methodLabel() }}
                                    @if ($expense->supplier)
                                        · {{ $expense->supplier->name }}
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right font-semibold text-slate-900">
                                Nu. {{ number_format($expense->amount, 2) }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    @if (! $expense->isVoided() && ! $expense->isGrLinked())
                                        @can('expense.edit')
                                            <a href="{{ route('admin.expenses.edit', $expense) }}"
                                               class="rounded-md p-1.5 text-slate-500 hover:bg-slate-100 hover:text-brand-600"
                                               title="Edit">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                            </a>
                                        @endcan
                                    @endif

                                    @if ($expense->canBeVoided())
                                        @can('expense.delete')
                                            <button type="button"
                                                    wire:click="openVoid({{ $expense->id }})"
                                                    class="rounded-md p-1.5 text-slate-500 hover:bg-amber-50 hover:text-amber-600"
                                                    title="Void">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                                </svg>
                                            </button>
                                        @endcan
                                    @endif

                                    @if ($expense->canBeDeleted())
                                        @can('expense.delete')
                                            <button type="button"
                                                    wire:click="deleteDraft({{ $expense->id }})"
                                                    wire:confirm="Delete this draft expense permanently?"
                                                    class="rounded-md p-1.5 text-slate-500 hover:bg-rose-50 hover:text-rose-600"
                                                    title="Delete draft">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        @endcan
                                    @endif

                                    @if ($expense->isGrLinked() && ! $expense->isVoided())
                                        <span class="text-[10px] uppercase text-slate-400" title="Managed from goods receipt">GR</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </x-admin.data-table>

                <div>{{ $expenses->links() }}</div>
            @endif
        </div>

        {{-- Category breakdown sidebar --}}
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <h2 class="text-sm font-bold text-slate-900">Category Breakdown</h2>
            <p class="mt-0.5 text-xs text-slate-500">Active totals · voided excluded</p>

            @if ($categoryBreakdown->isEmpty())
                <div class="py-8 text-center">
                    <p class="text-xs text-slate-400">No expenses in range.</p>
                </div>
            @else
                <div class="mt-4 space-y-3">
                    @foreach ($categoryBreakdown as $cat)
                        @php
                            $pct = $stats['total'] > 0 ? ($cat->total / $stats['total']) * 100 : 0;
                        @endphp
                        <div>
                            <div class="mb-1 flex items-center justify-between text-xs">
                                <span class="font-medium text-slate-800">{{ $cat->name }}</span>
                                <span class="font-semibold text-slate-900">Nu. {{ number_format($cat->total, 0) }}</span>
                            </div>
                            <div class="h-1.5 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full bg-brand-500" style="width: {{ $pct }}%;"></div>
                            </div>
                            <div class="mt-0.5 text-right text-[10px] text-slate-400">
                                {{ number_format($pct, 1) }}% of total
                            </div>
                        </div>
                    @endforeach

                    <div class="flex items-center justify-between border-t-2 border-slate-200 pt-3 text-sm">
                        <span class="font-semibold text-slate-700">Total</span>
                        <span class="font-bold text-brand-600">Nu. {{ number_format($stats['total'], 2) }}</span>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- ─── Void modal ─────────────────────────────────────── --}}
    <x-admin.modal name="void-expense" title="Void Expense" maxWidth="md">
        @if ($voidingId)
            @php $voiding = \App\Modules\Expense\Models\Expense::find($voidingId); @endphp
            @if ($voiding)
                <form wire:submit="voidExpense" class="space-y-4">
                    <x-admin.alert type="warning">
                        Voiding <strong>{{ $voiding->expense_number }}</strong> (Nu. {{ number_format($voiding->amount, 2) }}).
                        The expense remains visible in the list but is <strong>excluded from all totals and reports</strong>.
                        This action is reversible only by database administrators.
                    </x-admin.alert>

                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">
                            Reason <span class="text-rose-500">*</span>
                        </label>
                        <textarea wire:model="voidReason" rows="3"
                                  placeholder="e.g. Duplicate entry / entered in error"
                                  class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"></textarea>
                        @error('voidReason') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" wire:click="closeVoid"
                                class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                            Cancel
                        </button>
                        <button type="submit"
                                class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-medium text-white hover:bg-amber-700">
                            Void expense
                        </button>
                    </div>
                </form>
            @endif
        @endif
    </x-admin.modal>
</div>