<div class="space-y-6">

    {{-- ─── Page header ────────────────────────────────────── --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Menu Items</h1>
            <p class="mt-1 text-sm text-slate-500">Master catalog of all dishes. Daily availability is managed separately.</p>
        </div>
        @can('menu.create')
            <a href="{{ route('admin.menu.create') }}"
               class="inline-flex items-center gap-2 self-start rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-600 sm:self-auto">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add Menu Item
            </a>
        @endcan
    </div>

    @if ($statusMessage)
        <x-admin.alert :type="$statusType">{{ $statusMessage }}</x-admin.alert>
    @endif

    {{-- ─── Summary cards ──────────────────────────────────── --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <x-admin.stat-card label="Total Items" :value="$counts['total']" icon="book" color="brand" />
        <x-admin.stat-card label="Main Courses" :value="$counts['mains']" icon="book" color="rose" />
        <x-admin.stat-card label="Fast Food" :value="$counts['fastfood']" icon="book" color="sky" />
        <x-admin.stat-card label="Active" :value="$counts['active']" icon="book" color="emerald" />
    </div>

    {{-- ─── Filters ────────────────────────────────────────── --}}
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid gap-3 sm:grid-cols-12">

            <div class="sm:col-span-5">
                <label class="mb-1 block text-xs font-medium text-slate-600">Search</label>
                <div class="relative">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/>
                    </svg>
                    <input type="text"
                           wire:model.live.debounce.300ms="search"
                           placeholder="Search by name…"
                           class="w-full rounded-lg border border-slate-300 py-2 pl-9 pr-3 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                </div>
            </div>

            <div class="sm:col-span-3">
                <label class="mb-1 block text-xs font-medium text-slate-600">Type</label>
                <select wire:model.live="typeFilter"
                        class="w-full rounded-lg border border-slate-300 py-2 px-3 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                    <option value="">All types</option>
                    <option value="main">Main Course</option>
                    <option value="fastfood">Fast Food</option>
                </select>
            </div>

            <div class="sm:col-span-2">
                <label class="mb-1 block text-xs font-medium text-slate-600">Diet</label>
                <select wire:model.live="dietFilter"
                        class="w-full rounded-lg border border-slate-300 py-2 px-3 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                    <option value="">All</option>
                    <option value="veg">Veg</option>
                    <option value="nonveg">Non-Veg</option>
                </select>
            </div>

            <div class="flex items-end sm:col-span-2">
                <button type="button"
                        wire:click="clearFilters"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    Clear
                </button>
            </div>
        </div>
    </div>

    {{-- ─── Items table ────────────────────────────────────── --}}
    @if ($items->isEmpty())
        <div class="rounded-xl border border-slate-200 bg-white p-12 text-center">
            <p class="text-sm text-slate-500">No menu items match your filters.</p>
        </div>
    @else
        <x-admin.data-table :headers="['Item', 'Type', 'Price', 'Today', 'Active', '']">
            @foreach ($items as $item)
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            @if ($item->image_url)
                                <img src="{{ $item->image_url }}" alt="{{ $item->name }}"
                                     class="h-10 w-10 shrink-0 rounded-lg object-cover ring-1 ring-slate-200">
                            @else
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-400">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                            @endif
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="font-medium text-slate-900">{{ $item->name }}</span>
                                    <span class="{{ $item->is_veg ? 'text-emerald-600' : 'text-rose-600' }}">●</span>
                                </div>
                                <div class="text-xs text-slate-400">#{{ $item->id }} · {{ $item->slug }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <x-admin.badge :variant="$item->isMain() ? 'brand' : 'info'">
                            {{ $item->isMain() ? 'Main' : 'Fast Food' }}
                        </x-admin.badge>
                    </td>
                    <td class="px-4 py-3 font-semibold text-slate-900">
                        Nu. {{ number_format($item->price, 2) }}
                    </td>
                    <td class="px-4 py-3">
                        @if ($item->isPublishedToday())
                            <x-admin.badge variant="success">Yes</x-admin.badge>
                        @else
                            <span class="text-xs text-slate-400">No</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @can('menu.edit')
                            <button type="button"
                                    wire:click="toggleActive({{ $item->id }})"
                                    wire:loading.attr="disabled"
                                    class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors
                                           {{ $item->is_active ? 'bg-brand-500' : 'bg-slate-300' }}">
                                <span class="sr-only">Toggle active</span>
                                <span class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition
                                             {{ $item->is_active ? 'translate-x-4' : 'translate-x-0' }}"></span>
                            </button>
                        @else
                            <x-admin.badge :variant="$item->is_active ? 'success' : 'slate'">
                                {{ $item->is_active ? 'Active' : 'Inactive' }}
                            </x-admin.badge>
                        @endcan
                    </td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex items-center justify-end gap-1">
                            @can('menu.edit')
                                <a href="{{ route('admin.menu.edit', $item) }}"
                                   class="rounded-md p-1.5 text-slate-500 hover:bg-slate-100 hover:text-brand-600"
                                   title="Edit">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </a>
                            @endcan
                            @can('menu.delete')
                                <button type="button"
                                        wire:click="delete({{ $item->id }})"
                                        wire:confirm="Delete &quot;{{ $item->name }}&quot;? This cannot be undone."
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

        <div>{{ $items->links() }}</div>
    @endif
</div>