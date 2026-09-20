@php
    $isAvailableToday = $selectable ?? false;
    $isSelected = $selected ?? false;
@endphp

<div @class([
    'overflow-hidden rounded-xl border-2 bg-white shadow-sm transition',
    'border-brand-500 ring-1 ring-brand-200' => $isSelected,
    'border-slate-200 hover:shadow-md' => ! $isSelected,
])>
    {{-- Image --}}
    <div class="relative aspect-[16/10] w-full overflow-hidden bg-slate-100">
        @if ($item->image_url)
            <img src="{{ $item->image_url }}" alt="{{ $item->name }}" class="h-full w-full object-cover" />
        @else
            <div class="flex h-full w-full items-center justify-center text-slate-300">
                <svg class="h-10 w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
        @endif

        @unless ($isAvailableToday)
            <div class="absolute inset-0 flex items-center justify-center bg-slate-900/60">
                <span class="rounded-full bg-white px-3 py-1 text-[11px] font-semibold uppercase tracking-wider text-slate-700">
                    Not available today
                </span>
            </div>
        @endunless
    </div>

    {{-- Body --}}
    <div class="p-4">
        <div class="flex items-start justify-between gap-2">
            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2">
                    <h3 class="truncate text-sm font-semibold text-slate-900">{{ $item->name }}</h3>
                    <span class="{{ $item->is_veg ? 'text-emerald-600' : 'text-rose-600' }} shrink-0">●</span>
                </div>
                @if ($item->description)
                    <p class="mt-0.5 line-clamp-2 text-xs text-slate-500">{{ $item->description }}</p>
                @endif
            </div>
        </div>

        <div class="mt-3 flex items-center justify-between">
            <div class="text-base font-bold text-slate-900">
                Nu. {{ number_format($item->price, 0) }}
            </div>

            @auth
                @if ($isCustomer && $isAvailableToday)
                    @if ($isSelected)
                        <button type="button"
                                wire:click="toggleItem({{ $item->id }})"
                                wire:loading.attr="disabled"
                                class="inline-flex items-center gap-1 rounded-lg bg-emerald-500 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-emerald-600">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                            </svg>
                            Added
                        </button>
                    @else
                        <button type="button"
                                wire:click="toggleItem({{ $item->id }})"
                                wire:loading.attr="disabled"
                                class="inline-flex items-center gap-1 rounded-lg bg-brand-500 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-brand-600">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                            </svg>
                            Add
                        </button>
                    @endif
                @endif
            @endauth

            @guest
                <a href="{{ route('login') }}"
                   class="rounded-lg border border-brand-300 bg-white px-3 py-1.5 text-xs font-semibold text-brand-700 transition hover:bg-brand-50">
                    Sign in
                </a>
            @endguest
        </div>
    </div>
</div>