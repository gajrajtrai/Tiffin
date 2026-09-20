@php
    $user = auth()->user();
    $isCustomer = $user && $user->hasRole('Customer');
    $cartCount = count($cart);
@endphp

<div class="mx-auto max-w-5xl px-4 py-6 pb-32 sm:py-10 sm:pb-40">

    {{-- ─── Header ─────────────────────────────────────────── --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl">
            @if ($isToday) Today's Menu @else All Dishes @endif
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
                Browse our full catalog. Only items published for today can be ordered.
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

    @if ($isToday && ! $serviceDay->is_open)
        <div class="mb-6 rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">
            <strong>We're closed today.</strong> You can still browse — check back tomorrow for the next menu.
        </div>
    @endif

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

    {{-- ─── Sections ───────────────────────────────────────── --}}
    @if ($mains->isEmpty() && $fastFood->isEmpty())
        <div class="rounded-xl border border-slate-200 bg-white p-12 text-center">
            <h2 class="text-base font-semibold text-slate-900">
                {{ $isToday ? 'Menu not yet published' : 'No dishes in the catalog' }}
            </h2>
            <p class="mt-1 text-sm text-slate-500">
                @if ($isToday)
                    Check back soon, or browse our <a href="{{ route('menu.index', ['view' => 'all']) }}" class="text-brand-600 underline">full catalog</a>.
                @else
                    Come back shortly.
                @endif
            </p>
        </div>
    @else
        <div class="space-y-8">
            @if ($mains->isNotEmpty())
                <section>
                    <div class="mb-3 flex items-end justify-between">
                        <h2 class="text-lg font-bold text-slate-900">Main Courses</h2>
                        <span class="text-xs text-slate-400">{{ $mains->count() }} items</span>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($mains as $item)
                            @include('public.partials.menu-item-card', [
                                'item' => $item,
                                'isCustomer' => $isCustomer,
                                'selectable' => in_array($item->id, $publishedIds, true),
                                'selected' => in_array($item->id, $cart, true),
                            ])
                        @endforeach
                    </div>
                </section>
            @endif

            @if ($fastFood->isNotEmpty())
                <section>
                    <div class="mb-3 flex items-end justify-between">
                        <h2 class="text-lg font-bold text-slate-900">Fast Food</h2>
                        <span class="text-xs text-slate-400">{{ $fastFood->count() }} items</span>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($fastFood as $item)
                            @include('public.partials.menu-item-card', [
                                'item' => $item,
                                'isCustomer' => $isCustomer,
                                'selectable' => in_array($item->id, $publishedIds, true),
                                'selected' => in_array($item->id, $cart, true),
                            ])
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    @endif

    {{-- ─── Sticky cart bar ────────────────────────────────── --}}
    @if ($isCustomer && $cartCount > 0)
        <div class="fixed inset-x-0 bottom-16 z-30 px-4 pb-4 sm:bottom-4 sm:left-1/2 sm:right-auto sm:-translate-x-1/2 sm:px-0 sm:pb-6">
            <div class="mx-auto flex max-w-lg items-center gap-3 rounded-2xl border border-slate-200 bg-white p-3 shadow-xl">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-500 text-sm font-bold text-white">
                    {{ $cartCount }}
                </div>
                <div class="min-w-0 flex-1">
                    <div class="text-xs text-slate-500">{{ $cartCount }} {{ $cartCount === 1 ? 'item' : 'items' }} selected</div>
                    <div class="text-base font-bold text-slate-900">Nu. {{ number_format($cartTotal, 2) }}</div>
                </div>
                <button type="button" wire:click="clearCart"
                        class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50">
                    Clear
                </button>
                <button type="button" wire:click="openReview"
                        class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-600">
                    Review
                </button>
            </div>
        </div>
    @endif

    {{-- ─── Review modal ───────────────────────────────────── --}}
    <x-admin.modal name="review-order" title="Review your order" maxWidth="lg">
        @if ($showReviewModal)
            <div class="space-y-4">

                {{-- Items --}}
                <div class="rounded-lg border border-slate-200 bg-slate-50">
                    <div class="divide-y divide-slate-200">
                        @foreach ($cartItems as $item)
                            <div class="flex items-center gap-3 px-3 py-2">
                                <span class="text-base {{ $item->is_veg ? 'text-emerald-600' : 'text-rose-600' }}">●</span>
                                <div class="min-w-0 flex-1">
                                    <div class="truncate text-sm font-medium text-slate-900">{{ $item->name }}</div>
                                    <div class="text-xs text-slate-500">
                                        {{ $item->isMain() ? 'Main Course' : 'Fast Food' }}
                                    </div>
                                </div>
                                <div class="shrink-0 text-sm font-semibold text-slate-900">
                                    Nu. {{ number_format($item->price, 2) }}
                                </div>
                                <button type="button" wire:click="removeItem({{ $item->id }})"
                                        class="ml-1 rounded p-1 text-slate-400 hover:bg-rose-50 hover:text-rose-600"
                                        title="Remove">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Delivery method --}}
                <div>
                    <div class="mb-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Delivery method</div>
                    <div class="grid grid-cols-2 gap-2">
                        <button type="button" wire:click="$set('deliveryMethod', 'delivery')"
                                @class([
                                    'flex items-center justify-center gap-2 rounded-lg border-2 px-3 py-2.5 text-sm font-medium transition',
                                    'border-brand-500 bg-brand-50 text-brand-700' => $deliveryMethod === 'delivery',
                                    'border-slate-200 bg-white text-slate-700 hover:border-brand-300' => $deliveryMethod !== 'delivery',
                                ])
                                @if ($serviceDay->isPastCutoff()) disabled @endif>
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1"/>
                            </svg>
                            Delivery
                        </button>
                        <button type="button" wire:click="$set('deliveryMethod', 'pickup')"
                                @class([
                                    'flex items-center justify-center gap-2 rounded-lg border-2 px-3 py-2.5 text-sm font-medium transition',
                                    'border-brand-500 bg-brand-50 text-brand-700' => $deliveryMethod === 'pickup',
                                    'border-slate-200 bg-white text-slate-700 hover:border-brand-300' => $deliveryMethod !== 'pickup',
                                ])>
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l9-9 9 9M5 10v10a1 1 0 001 1h3a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1h3a1 1 0 001-1V10"/>
                            </svg>
                            Pickup
                        </button>
                    </div>
                    @if ($deliveryMethod === 'delivery' && $serviceDay->isPastCutoff())
                        <p class="mt-2 text-xs text-amber-600">
                            Delivery cut-off was at {{ $serviceDay->effectiveCutoffTime() }} — please choose pickup.
                        </p>
                    @elseif ($deliveryMethod === 'delivery')
                        <p class="mt-2 text-xs text-slate-500">
                            Delivered to college gate · {{ $serviceDay->effectiveCutoffTime() }} – 14:00
                        </p>
                    @else
                        <p class="mt-2 text-xs text-slate-500">
                            Collect from our counter · any time during opening hours
                        </p>
                    @endif
                </div>

                {{-- Money summary --}}
                <div class="rounded-lg border border-slate-200 bg-white">
                    <div class="flex items-center justify-between px-3 py-2 text-sm">
                        <span class="text-slate-600">Order total</span>
                        <span class="font-semibold text-slate-900">Nu. {{ number_format($cartTotal, 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between border-t border-slate-200 px-3 py-2 text-sm">
                        <span class="text-slate-600">Wallet balance</span>
                        <span class="text-slate-700">Nu. {{ number_format($walletBalance, 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between border-t border-slate-200 bg-slate-50 px-3 py-2 text-sm">
                        <span class="font-medium text-slate-700">Balance after order</span>
                        <span class="font-bold {{ $hasSufficientBalance ? 'text-emerald-600' : 'text-rose-600' }}">
                            Nu. {{ number_format($walletAfter, 2) }}
                        </span>
                    </div>
                </div>

                @if (! $hasSufficientBalance)
                    <div class="rounded-lg border border-rose-200 bg-rose-50 p-3 text-xs text-rose-800">
                        <strong>Insufficient balance.</strong>
                        Top up at least <strong>Nu. {{ number_format(abs($walletAfter), 2) }}</strong> before placing this order.
                        <a href="{{ route('wallet.index') }}" class="ml-1 underline">Go to wallet →</a>
                    </div>
                @endif

                @if ($orderError)
                    <div class="rounded-lg border border-rose-200 bg-rose-50 p-3 text-xs text-rose-800">
                        {{ $orderError }}
                    </div>
                @endif
            </div>

            <x-slot:footer>
                <button type="button" wire:click="closeReview"
                        class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    Keep browsing
                </button>
                <button type="button" wire:click="confirm"
                        wire:loading.attr="disabled" wire:target="confirm"
                        @if (! $hasSufficientBalance || empty($cart) || ($deliveryMethod === 'delivery' && $serviceDay->isPastCutoff())) disabled @endif
                        class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-600 disabled:cursor-not-allowed disabled:opacity-50">
                    <svg wire:loading.remove wire:target="confirm" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <svg wire:loading wire:target="confirm" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    <span wire:loading.remove wire:target="confirm">Place order — Nu. {{ number_format($cartTotal, 2) }}</span>
                    <span wire:loading wire:target="confirm">Placing…</span>
                </button>
            </x-slot:footer>
        @endif
    </x-admin.modal>
</div>