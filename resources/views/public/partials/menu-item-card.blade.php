<div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm transition hover:shadow-md">
    {{-- Image --}}
    <div class="aspect-[16/10] w-full overflow-hidden bg-slate-100">
        @if ($item->image_url)
            <img src="{{ $item->image_url }}"
                 alt="{{ $item->name }}"
                 class="h-full w-full object-cover" />
        @else
            <div class="flex h-full w-full items-center justify-center text-slate-300">
                <svg class="h-10 w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
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

        <div class="mt-3 flex items-center justify-between">
            <div class="text-base font-bold text-slate-900">
                Nu. {{ number_format($item->price, 0) }}
            </div>

            @auth
                <button type="button"
                        wire:click="startOrder({{ $item->id }})"
                        class="rounded-lg bg-brand-500 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-brand-600">
                    Order
                </button>
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