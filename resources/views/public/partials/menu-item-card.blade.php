@php
    $isAvailableToday = $selectable ?? false;
    $isSoldOut = $item->is_sold_out ?? false;
    $qty = $selectedQty ?? 0;
    $inCart = $qty > 0;
    $canOrder = $isAvailableToday && ! $isSoldOut;
@endphp

<div @class([
    'overflow-hidden rounded-xl border-2 bg-white shadow-sm transition',
    'border-brand-500 ring-1 ring-brand-200' => $inCart,
    'border-slate-200 hover:shadow-md' => ! $inCart && $canOrder,
    'border-slate-200 opacity-75' => ! $canOrder,
])>
    {{-- Image --}}
    <div class="relative aspect-[16/10] w-full overflow-hidden bg-slate-100">
        @if ($item->image_url)
            <img src="{{ $item->image_url }}" alt="{{ $item->name }}"
                 class="h-full w-full object-cover {{ $isSoldOut ? 'grayscale' : '' }}" />
        @else
            <div class="flex h-full w-full items-center justify-center text-slate-300">
                <svg class="h-10 w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
        @endif

        @if ($inCart)
            <div class="absolute right-2 top-2 flex h-7 w-7 items-center justify-center rounded-full bg-brand-500 text-xs font-bold text-white shadow">
                {{ $qty }}
            </div>
        @endif

        @if ($isSoldOut)
            <div class="absolute inset-0 flex items-center justify-center bg-slate-900/60">
                <span class="rounded-full bg-white px-3 py-1 text-[11px] font-bold uppercase tracking-wider text-rose-700">
                    Sold out today
                </span>
            </div>
        @elseif (! $isAvailableToday)
            <div class="absolute inset-0 flex items-center justify-center bg-slate-900/60">
                <span class="rounded-full bg-white px-3 py-1 text-[11px] font-semibold uppercase tracking-wider text-slate-700">
                    Not available today
                </span>
            </div>
        @endif
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

        <div class="mt-3 flex items-center justify-between gap-2">
            <div class="text-base font-bold text-slate-900">
                Nu. {{ number_format($item->price, 0) }}
            </div>

            @auth
                @if ($isCustomer && $canOrder)
                    @if ($inCart)
                        <div class="flex items-center gap-1">
                            <div class="inline-flex items-center overflow-hidden rounded-lg border border-brand-500 bg-brand-50">
                                <button type="button" wire:click="decrementItem({{ $item->id }})"
                                        wire:loading.attr="disabled"
                                        class="flex h-8 w-8 items-center justify-center text-brand-700 transition hover:bg-brand-100">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20 12H4"/>
                                    </svg>
                                </button>
                                <input type="number" min="1"
                                       max="{{ \App\Modules\Customer\Http\Livewire\MenuBrowse::MAX_QTY_PER_ITEM }}"
                                       value="{{ $qty }}"
                                       wire:change="setQuantity({{ $item->id }}, $event.target.value)"
                                       class="h-8 w-10 border-0 bg-transparent text-center text-sm font-bold text-brand-700 focus:outline-none focus:ring-0 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none" />
                                <button type="button" wire:click="incrementItem({{ $item->id }})"
                                        wire:loading.attr="disabled"
                                        class="flex h-8 w-8 items-center justify-center text-brand-700 transition hover:bg-brand-100">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                                    </svg>
                                </button>
                            </div>
                            <button type="button" wire:click="removeItem({{ $item->id }})"
                                    wire:confirm="Remove {{ $item->name }} from your order?"
                                    class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    @else
                        <button type="button" wire:click="incrementItem({{ $item->id }})"
                                wire:loading.attr="disabled"
                                class="inline-flex items-center gap-1 rounded-lg bg-brand-500 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-brand-600">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                            </svg>
                            Add
                        </button>
                    @endif
                @elseif ($isCustomer && $isSoldOut)
                    <span class="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-500">
                        Sold out
                    </span>
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