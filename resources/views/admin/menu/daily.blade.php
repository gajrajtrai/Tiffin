<div class="space-y-6">

    {{-- ─── Back link ──────────────────────────────────────── --}}
    <a href="{{ route('admin.menu.index') }}"
       class="inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-brand-600">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Back to Menu Catalog
    </a>

    @if ($statusMessage)
        <x-admin.alert :type="$statusType">{{ $statusMessage }}</x-admin.alert>
    @endif

    {{-- ─── Date navigation ────────────────────────────────── --}}
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">

            <div class="flex flex-wrap items-center gap-2">
                <button type="button" wire:click="previousDay"
                        class="rounded-lg border border-slate-300 bg-white p-2 text-slate-600 hover:bg-slate-50"
                        title="Previous day">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>

                <input type="date" wire:model.live="date"
                       class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />

                <button type="button" wire:click="nextDay"
                        class="rounded-lg border border-slate-300 bg-white p-2 text-slate-600 hover:bg-slate-50"
                        title="Next day">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>

                <button type="button" wire:click="goToday"
                        class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    Today
                </button>

                <div class="ml-2 text-sm text-slate-600">
                    <span class="font-semibold text-slate-900">{{ $selectedDate->format('l, F j, Y') }}</span>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button type="button" wire:click="copyFromYesterday"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                    Copy yesterday
                </button>

                <button type="button" wire:click="clearDay"
                        wire:confirm="Remove all items for {{ $selectedDate->format('M j') }}?"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-rose-600 hover:bg-rose-50 hover:border-rose-300">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Clear day
                </button>
            </div>
        </div>
    </div>

    {{-- ─── Service day banner ─────────────────────────────── --}}
    <div class="rounded-xl border p-4 shadow-sm {{ $serviceDay->is_open ? 'border-emerald-200 bg-emerald-50' : 'border-rose-200 bg-rose-50' }}">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg
                            {{ $serviceDay->is_open ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <div class="text-sm font-bold {{ $serviceDay->is_open ? 'text-emerald-800' : 'text-rose-800' }}">
                        {{ $serviceDay->is_open ? 'Open for service' : 'Closed' }}
                    </div>
                    <div class="mt-0.5 text-xs {{ $serviceDay->is_open ? 'text-emerald-700' : 'text-rose-700' }}">
                        Cut-off: <strong>{{ $serviceDay->effectiveCutoffTime() }}</strong>
                        · Capacity: <strong>{{ $serviceDay->effectiveCapacity() }}</strong> delivery orders
                        @if ($serviceDay->isPastCutoff() && $serviceDay->is_open)
                            · <span class="font-semibold">Past cut-off for delivery</span>
                        @endif
                    </div>
                </div>
            </div>
            <button type="button" wire:click="toggleServiceDay"
                    class="rounded-lg border bg-white px-4 py-2 text-sm font-medium
                           {{ $serviceDay->is_open
                               ? 'border-rose-300 text-rose-700 hover:bg-rose-50'
                               : 'border-emerald-300 text-emerald-700 hover:bg-emerald-50' }}">
                {{ $serviceDay->is_open ? 'Mark closed' : 'Open for service' }}
            </button>
        </div>
    </div>

    {{-- ─── Stats ───────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-medium uppercase tracking-wider text-slate-500">Mains Published</div>
            <div class="mt-1 text-2xl font-bold {{ $stats['mainsPublished'] >= $stats['maxMains'] ? 'text-amber-600' : 'text-slate-900' }}">
                {{ $stats['mainsPublished'] }} <span class="text-base font-normal text-slate-400">/ {{ $stats['maxMains'] }}</span>
            </div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-medium uppercase tracking-wider text-slate-500">Fast Food Published</div>
            <div class="mt-1 text-2xl font-bold text-slate-900">{{ $stats['fastfoodPublished'] }}</div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-medium uppercase tracking-wider text-slate-500">Orders for Day</div>
            <div class="mt-1 text-2xl font-bold text-slate-900">{{ $stats['orderCount'] }}</div>
            @if ($stats['orderCount'] > 0)
                <div class="mt-1 text-xs font-medium text-amber-600">Changing menu now may confuse customers</div>
            @endif
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-medium uppercase tracking-wider text-slate-500">Revenue</div>
            <div class="mt-1 text-2xl font-bold text-slate-900">
                Nu. {{ number_format($stats['orderRevenue'], 0) }}
            </div>
        </div>
    </div>

    {{-- ─── Mains + Fast Food ─────────────────────────────── --}}
    <div class="grid gap-6 lg:grid-cols-2">

        {{-- Mains --}}
        <div>
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-sm font-bold text-slate-900">Main Courses</h2>
                <span class="text-xs text-slate-500">
                    {{ $stats['mainsPublished'] }} of {{ $stats['maxMains'] }} published
                    @if ($stats['mainsPublished'] >= $stats['maxMains'])
                        <span class="ml-1 font-semibold text-amber-600">· limit reached</span>
                    @endif
                </span>
            </div>

            @if ($mains->isEmpty())
                <div class="rounded-xl border border-slate-200 bg-white p-8 text-center">
                    <p class="text-sm text-slate-500">No active main courses. Add items in the Menu Catalog.</p>
                </div>
            @else
                <div class="space-y-2">
                    @foreach ($mains as $item)
                        @php $isPublished = in_array($item->id, $publishedIds); @endphp
                        <button type="button"
                                wire:click="toggleItem({{ $item->id }})"
                                @class([
                                    'w-full rounded-xl border-2 p-3 text-left transition',
                                    'border-brand-500 bg-brand-50 ring-1 ring-brand-200' => $isPublished,
                                    'border-slate-200 bg-white hover:border-brand-300 hover:bg-brand-50/30' => ! $isPublished,
                                ])>
                            <div class="flex items-center gap-3">
                                @if ($item->image_url)
                                    <img src="{{ $item->image_url }}" alt="{{ $item->name }}"
                                         class="h-12 w-12 shrink-0 rounded-lg object-cover ring-1 ring-slate-200" />
                                @else
                                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-400">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                @endif

                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <span class="truncate font-medium text-slate-900">{{ $item->name }}</span>
                                        <span class="{{ $item->is_veg ? 'text-emerald-600' : 'text-rose-600' }}">●</span>
                                    </div>
                                    <div class="text-xs text-slate-500">Nu. {{ number_format($item->price, 2) }}</div>
                                </div>

                                <div class="shrink-0">
                                    @if ($isPublished)
                                        <x-admin.badge variant="success">Published</x-admin.badge>
                                    @else
                                        <span class="text-xs font-medium text-slate-400">Click to publish</span>
                                    @endif
                                </div>
                            </div>
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Fast food --}}
        <div>
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-sm font-bold text-slate-900">Fast Food</h2>
                @if ($fastFood->isNotEmpty())
                    <button type="button" wire:click="publishAllFastFood"
                            class="text-xs font-medium text-brand-600 hover:text-brand-700">
                        Publish all
                    </button>
                @endif
            </div>

            @if ($fastFood->isEmpty())
                <div class="rounded-xl border border-slate-200 bg-white p-8 text-center">
                    <p class="text-sm text-slate-500">No active fast food items. Add items in the Menu Catalog.</p>
                </div>
            @else
                <div class="space-y-2">
                    @foreach ($fastFood as $item)
                        @php $isPublished = in_array($item->id, $publishedIds); @endphp
                        <button type="button"
                                wire:click="toggleItem({{ $item->id }})"
                                @class([
                                    'w-full rounded-xl border-2 p-3 text-left transition',
                                    'border-brand-500 bg-brand-50 ring-1 ring-brand-200' => $isPublished,
                                    'border-slate-200 bg-white hover:border-brand-300 hover:bg-brand-50/30' => ! $isPublished,
                                ])>
                            <div class="flex items-center gap-3">
                                @if ($item->image_url)
                                    <img src="{{ $item->image_url }}" alt="{{ $item->name }}"
                                         class="h-12 w-12 shrink-0 rounded-lg object-cover ring-1 ring-slate-200" />
                                @else
                                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-400">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                @endif

                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <span class="truncate font-medium text-slate-900">{{ $item->name }}</span>
                                        <span class="{{ $item->is_veg ? 'text-emerald-600' : 'text-rose-600' }}">●</span>
                                    </div>
                                    <div class="text-xs text-slate-500">Nu. {{ number_format($item->price, 2) }}</div>
                                </div>

                                <div class="shrink-0">
                                    @if ($isPublished)
                                        <x-admin.badge variant="success">Published</x-admin.badge>
                                    @else
                                        <span class="text-xs font-medium text-slate-400">Click to publish</span>
                                    @endif
                                </div>
                            </div>
                        </button>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>