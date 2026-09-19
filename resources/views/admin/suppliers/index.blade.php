<div class="space-y-6">

    {{-- ─── Page header ────────────────────────────────────── --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Suppliers</h1>
            <p class="mt-1 text-sm text-slate-500">Vendors you purchase from.</p>
        </div>
        @can('supplier.create')
            <a href="{{ route('admin.suppliers.create') }}"
               class="inline-flex items-center gap-2 self-start rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-600 sm:self-auto">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add Supplier
            </a>
        @endcan
    </div>

    @if ($statusMessage)
        <x-admin.alert :type="$statusType">{{ $statusMessage }}</x-admin.alert>
    @endif

    {{-- ─── Stats ──────────────────────────────────────────── --}}
    <div class="grid grid-cols-3 gap-4">
        <x-admin.stat-card label="Total" :value="$counts['total']" icon="truck" color="brand" />
        <x-admin.stat-card label="Active" :value="$counts['active']" icon="truck" color="emerald" />
        <x-admin.stat-card label="Inactive" :value="$counts['inactive']" icon="truck" color="slate" />
    </div>

    {{-- ─── Filters ────────────────────────────────────────── --}}
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid gap-3 sm:grid-cols-12">
            <div class="sm:col-span-8">
                <label class="mb-1 block text-xs font-medium text-slate-600">Search</label>
                <div class="relative">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/>
                    </svg>
                    <input type="text" wire:model.live.debounce.300ms="search"
                           placeholder="Name, contact, mobile, or supplies"
                           class="w-full rounded-lg border border-slate-300 py-2 pl-9 pr-3 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                </div>
            </div>

            <div class="flex items-end sm:col-span-2">
                <button type="button" wire:click="$toggle('activeOnly')"
                        class="w-full rounded-lg border px-3 py-2 text-sm font-medium transition
                               {{ $activeOnly === '1'
                                   ? 'border-emerald-300 bg-emerald-50 text-emerald-700'
                                   : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50' }}">
                    {{ $activeOnly === '1' ? '✓ ' : '' }}Active only
                </button>
            </div>

            <div class="flex items-end sm:col-span-2">
                <button type="button" wire:click="clearFilters"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    Clear
                </button>
            </div>
        </div>
    </div>

    {{-- ─── Table ──────────────────────────────────────────── --}}
    @if ($suppliers->isEmpty())
        <div class="rounded-xl border border-slate-200 bg-white p-12 text-center">
            <p class="text-sm text-slate-500">No suppliers match the current filters.</p>
        </div>
    @else
        <x-admin.data-table :headers="['Supplier', 'Contact', 'Supplies', 'Status', '']">
            @foreach ($suppliers as $s)
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3">
                        <div class="font-medium text-slate-900">{{ $s->name }}</div>
                        @if ($s->address)
                            <div class="text-xs text-slate-400">{{ \Illuminate\Support\Str::limit($s->address, 60) }}</div>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @if ($s->contact_person)
                            <div class="text-sm text-slate-700">{{ $s->contact_person }}</div>
                        @endif
                        @if ($s->mobile)
                            <div class="text-xs text-slate-500">{{ $s->mobile }}</div>
                        @endif
                        @if ($s->email)
                            <div class="text-xs text-slate-500">{{ $s->email }}</div>
                        @endif
                        @if (! $s->contact_person && ! $s->mobile && ! $s->email)
                            <span class="text-xs text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-xs text-slate-600">
                        {{ $s->supplies ?: '—' }}
                    </td>
                    <td class="px-4 py-3">
                        @can('supplier.edit')
                            <button type="button"
                                    wire:click="toggleActive({{ $s->id }})"
                                    class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors
                                           {{ $s->is_active ? 'bg-brand-500' : 'bg-slate-300' }}">
                                <span class="sr-only">Toggle active</span>
                                <span class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition
                                             {{ $s->is_active ? 'translate-x-4' : 'translate-x-0' }}"></span>
                            </button>
                        @else
                            <x-admin.badge :variant="$s->is_active ? 'success' : 'slate'">
                                {{ $s->is_active ? 'Active' : 'Inactive' }}
                            </x-admin.badge>
                        @endcan
                    </td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex items-center justify-end gap-1">
                            @can('supplier.edit')
                                <a href="{{ route('admin.suppliers.edit', $s) }}"
                                   class="rounded-md p-1.5 text-slate-500 hover:bg-slate-100 hover:text-brand-600"
                                   title="Edit">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </a>
                            @endcan
                            @can('supplier.delete')
                                <button type="button"
                                        wire:click="delete({{ $s->id }})"
                                        wire:confirm="Delete &quot;{{ $s->name }}&quot;?"
                                        class="rounded-md p-1.5 text-slate-500 hover:bg-rose-50 hover:text-rose-600"
                                        title="Delete">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            @endcan
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-admin.data-table>

        <div>{{ $suppliers->links() }}</div>
    @endif
</div>