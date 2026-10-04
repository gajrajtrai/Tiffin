<div class="space-y-6" x-data>

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

    {{-- ─── Week navigation ────────────────────────────────── --}}
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">

            <div class="flex flex-wrap items-center gap-2">
                @php
                    $currentWeekStart = now()->startOfWeek(\Illuminate\Support\Carbon::MONDAY);
                    $atCurrentWeek = $dates[0]->startOfWeek(\Illuminate\Support\Carbon::MONDAY)->lte($currentWeekStart);
                @endphp
                <button type="button" wire:click="previousWeek"
                        @disabled($atCurrentWeek)
                        class="rounded-lg border border-slate-300 bg-white p-2 text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:bg-white"
                        title="{{ $atCurrentWeek ? 'Cannot go before the current week' : 'Previous week' }}">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>

                <button type="button" wire:click="currentWeek"
                        class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    This Week
                </button>

                <button type="button" wire:click="nextWeek"
                        class="rounded-lg border border-slate-300 bg-white p-2 text-slate-600 hover:bg-slate-50"
                        title="Next week">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>

                <div class="ml-2 text-sm text-slate-600">
                    <span class="font-semibold text-slate-900">
                        {{ $dates[0]->format('M j') }} – {{ $dates[6]->format('M j, Y') }}
                    </span>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button type="button" wire:click="copyMondayToWeekdays"
                        wire:confirm="Copy Monday's menu to Tuesday–Friday?"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                    Copy Mon → Weekdays
                </button>

                <button type="button" wire:click="clearWeek"
                        wire:confirm="Clear the entire week? This removes all published items."
                        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-rose-600 hover:bg-rose-50 hover:border-rose-300">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Clear week
                </button>
            </div>
        </div>
    </div>

    {{-- ─── Grid ───────────────────────────────────────────── --}}
    @if ($mains->isEmpty() && $fastFood->isEmpty())
        <div class="rounded-xl border border-slate-200 bg-white p-12 text-center">
            <p class="text-sm text-slate-500">No active menu items. Add items in the Menu Catalog.</p>
        </div>
    @else
        <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
            <table class="w-full border-collapse text-sm">
                <thead>
                    {{-- Day headers --}}
                    <tr class="border-b border-slate-200 bg-slate-50">
                        <th class="sticky left-0 z-10 min-w-[200px] border-r border-slate-200 bg-slate-50 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                            Item
                        </th>
                        @foreach ($dates as $date)
                            @php $key = $date->toDateString(); $sd = $serviceDays[$key]; @endphp
                            <th class="min-w-[110px] px-2 py-3 text-center">
                                <div class="flex flex-col items-center gap-1">
                                    <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        {{ $date->format('D') }}
                                    </div>
                                    <div class="text-sm font-bold text-slate-900">
                                        {{ $date->format('M j') }}
                                    </div>
                                    <button type="button"
                                            wire:click="toggleServiceDay('{{ $key }}')"
                                            class="rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase
                                                   {{ $sd->is_open ? 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200' : 'bg-rose-100 text-rose-700 hover:bg-rose-200' }}"
                                            title="Click to {{ $sd->is_open ? 'close' : 'open' }}">
                                        {{ $sd->is_open ? 'Open' : 'Closed' }}
                                    </button>
                                    @php $mc = $mainCountsByDate[$key]; @endphp
                                    @if ($mc > 0)
                                        <div class="text-[10px] {{ $mc >= 2 ? 'text-amber-600 font-semibold' : 'text-slate-400' }}">
                                            {{ $mc }}/2 mains
                                        </div>
                                    @endif
                                </div>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">

                    {{-- Mains section --}}
                    @if ($mains->isNotEmpty())
                        <tr class="bg-brand-50/50">
                            <td colspan="8" class="sticky left-0 border-r border-slate-200 px-4 py-2 text-xs font-bold uppercase tracking-wider text-brand-700">
                                Main Courses
                            </td>
                        </tr>
                        @foreach ($mains as $item)
                            <tr class="hover:bg-slate-50">
                                <td class="sticky left-0 z-10 border-r border-slate-200 bg-white px-4 py-2.5">
                                    <div class="flex items-center gap-2">
                                        <span class="truncate text-sm font-medium text-slate-900">{{ $item->name }}</span>
                                        <span class="{{ $item->is_veg ? 'text-emerald-600' : 'text-rose-600' }}">●</span>
                                    </div>
                                    <div class="text-xs text-slate-500">Nu. {{ number_format($item->price, 2) }}</div>
                                </td>
                                @foreach ($dates as $date)
                                    @php
                                        $key = $date->toDateString();
                                        $isPublished = in_array($item->id, $publishedByDate[$key]);
                                    @endphp
                                    <td class="px-2 py-2 text-center">
                                        <button type="button"
                                                wire:click="toggle({{ $item->id }}, '{{ $key }}')"
                                                @class([
                                                    'mx-auto flex h-8 w-8 items-center justify-center rounded-lg border-2 transition',
                                                    'border-brand-500 bg-brand-500 text-white' => $isPublished,
                                                    'border-slate-200 bg-white text-slate-300 hover:border-brand-300 hover:text-brand-400' => ! $isPublished,
                                                ])
                                                title="{{ $isPublished ? 'Click to unpublish' : 'Click to publish' }}">
                                            @if ($isPublished)
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                                </svg>
                                            @endif
                                        </button>
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    @endif

                    {{-- Fast food section --}}
                    @if ($fastFood->isNotEmpty())
                        <tr class="bg-sky-50/50">
                            <td colspan="8" class="sticky left-0 border-r border-slate-200 px-4 py-2 text-xs font-bold uppercase tracking-wider text-sky-700">
                                Fast Food
                            </td>
                        </tr>
                        @foreach ($fastFood as $item)
                            <tr class="hover:bg-slate-50">
                                <td class="sticky left-0 z-10 border-r border-slate-200 bg-white px-4 py-2.5">
                                    <div class="flex items-center gap-2">
                                        <span class="truncate text-sm font-medium text-slate-900">{{ $item->name }}</span>
                                        <span class="{{ $item->is_veg ? 'text-emerald-600' : 'text-rose-600' }}">●</span>
                                    </div>
                                    <div class="text-xs text-slate-500">Nu. {{ number_format($item->price, 2) }}</div>
                                </td>
                                @foreach ($dates as $date)
                                    @php
                                        $key = $date->toDateString();
                                        $isPublished = in_array($item->id, $publishedByDate[$key]);
                                    @endphp
                                    <td class="px-2 py-2 text-center">
                                        <button type="button"
                                                wire:click="toggle({{ $item->id }}, '{{ $key }}')"
                                                @class([
                                                    'mx-auto flex h-8 w-8 items-center justify-center rounded-lg border-2 transition',
                                                    'border-sky-500 bg-sky-500 text-white' => $isPublished,
                                                    'border-slate-200 bg-white text-slate-300 hover:border-sky-300 hover:text-sky-400' => ! $isPublished,
                                                ])
                                                title="{{ $isPublished ? 'Click to unpublish' : 'Click to publish' }}">
                                            @if ($isPublished)
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                                </svg>
                                            @endif
                                        </button>
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>

        <p class="text-xs text-slate-500">
            Click any cell to toggle. Main course limit is 2 per day — the count appears under each day header.
            Click a day's Open/Closed badge to change its service status.
        </p>
    @endif
</div>