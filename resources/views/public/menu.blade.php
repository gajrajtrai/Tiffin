@php
    $user = auth()->user();
    $isCustomer = $user && $user->hasRole('Customer');
    $canOrder = $isCustomer; // guests must sign in
@endphp

<div class="mx-auto max-w-5xl px-4 py-6 sm:py-10">

    {{-- ─── Header ─────────────────────────────────────────── --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl">
            @if ($isToday)
                Today's Menu
            @else
                All Dishes
            @endif
        </h1>
        <p class="mt-1 text-sm text-slate-600">
            @if ($isToday)
                {{ now()->format('l, F j') }}
                @if (! $serviceDay->is_open)
                    · <span class="font-medium text-rose-600">Closed today</span>
                @elseif ($serviceDay->isPastCutoff())
                    · <span class="font-medium text-amber-600">Delivery cut-off passed — pickup still available</span>
                @else
                    · Delivery cut-off at {{ $serviceDay->effectiveCutoffTime() }}
                @endif
            @else
                Browse our full catalog. Today's available items are the ones you can currently order.
            @endif
        </p>
    </div>

    {{-- ─── View toggle ────────────────────────────────────── --}}
    <div class="mb-6 inline-flex rounded-lg border border-slate-200 bg-white p-0.5 shadow-sm">
        <button type="button" wire:click="setView('today')"
                @class([
                    'rounded-md px-4 py-1.5 text-sm font-medium transition',
                    'bg-brand-500 text-white shadow-sm' => $isToday,
                    'text-slate-600 hover:text-slate-900' => ! $isToday,
                ])>
            Today
        </button>
        <button type="button" wire:click="setView('all')"
                @class([
                    'rounded-md px-4 py-1.5 text-sm font-medium transition',
                    'bg-brand-500 text-white shadow-sm' => ! $isToday,
                    'text-slate-600 hover:text-slate-900' => $isToday,
                ])>
            All dishes
        </button>
    </div>

    {{-- ─── Closed-day banner ──────────────────────────────── --}}
    @if ($isToday && ! $serviceDay->is_open)
        <div class="mb-6 rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">
            <strong>We're closed today.</strong> You can still browse the full catalog —
            check back tomorrow for the next menu.
        </div>
    @endif

    {{-- ─── Notice (order placeholder flash) ──────────────── --}}
    @if ($notice)
        <div x-data="{ show: true }"
             x-show="show"
             x-init="setTimeout(() => show = false, 6000)"
             x-transition
             class="mb-6 flex items-start justify-between gap-3 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
            <div class="flex items-start gap-2">
                <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>{{ $notice }}</span>
            </div>
            <button type="button" wire:click="dismissNotice" class="text-amber-600 hover:text-amber-800">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    @endif

    {{-- ─── Guest CTA ──────────────────────────────────────── --}}
    @guest
        <div class="mb-6 flex flex-col items-start gap-3 rounded-xl border border-brand-200 bg-brand-50 p-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-start gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-100 text-brand-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
                <div>
                    <div class="text-sm font-semibold text-brand-900">Sign in to order</div>
                    <div class="text-xs text-brand-700">Create an account or sign in with your mobile to place an order.</div>
                </div>
            </div>
            <div class="flex gap-2 self-stretch sm:self-auto">
                <a href="{{ route('login') }}"
                   class="flex-1 rounded-lg bg-brand-500 px-4 py-2 text-center text-sm font-semibold text-white hover:bg-brand-600 sm:flex-none">
                    Sign in
                </a>
                <a href="{{ route('register') }}"
                   class="flex-1 rounded-lg border border-brand-300 bg-white px-4 py-2 text-center text-sm font-semibold text-brand-700 hover:bg-brand-50 sm:flex-none">
                    Register
                </a>
            </div>
        </div>
    @endguest

    {{-- ─── Menu sections ──────────────────────────────────── --}}
    @if ($mains->isEmpty() && $fastFood->isEmpty())
        <div class="rounded-xl border border-slate-200 bg-white p-12 text-center">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
            </div>
            <h2 class="mt-3 text-base font-semibold text-slate-900">
                {{ $isToday ? 'Menu not yet published' : 'No dishes in the catalog' }}
            </h2>
            <p class="mt-1 text-sm text-slate-500">
                @if ($isToday)
                    Check back soon, or browse our <a href="{{ route('menu.index', ['view' => 'all']) }}" class="text-brand-600 underline">full catalog</a>.
                @else
                    Our chefs are still working on the menu. Come back shortly.
                @endif
            </p>
        </div>
    @else
        <div class="space-y-8">

            {{-- Mains --}}
            @if ($mains->isNotEmpty())
                <section>
                    <div class="mb-3 flex items-end justify-between">
                        <div>
                            <h2 class="text-lg font-bold text-slate-900">Main Courses</h2>
                            <p class="text-xs text-slate-500">
                                @if ($isToday)
                                    Available today
                                @else
                                    Our rotating main dishes
                                @endif
                            </p>
                        </div>
                        <span class="text-xs text-slate-400">{{ $mains->count() }} items</span>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($mains as $item)
                            @include('public.partials.menu-item-card', ['item' => $item, 'canOrder' => $canOrder, 'isToday' => $isToday])
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- Fast food --}}
            @if ($fastFood->isNotEmpty())
                <section>
                    <div class="mb-3 flex items-end justify-between">
                        <div>
                            <h2 class="text-lg font-bold text-slate-900">Fast Food</h2>
                            <p class="text-xs text-slate-500">
                                @if ($isToday)
                                    Snacks, sides, and drinks
                                @else
                                    Everything else we make
                                @endif
                            </p>
                        </div>
                        <span class="text-xs text-slate-400">{{ $fastFood->count() }} items</span>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($fastFood as $item)
                            @include('public.partials.menu-item-card', ['item' => $item, 'canOrder' => $canOrder, 'isToday' => $isToday])
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    @endif
</div>