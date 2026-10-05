<div class="mx-auto max-w-3xl space-y-6">

    <div class="flex items-center justify-between">
        <a href="{{ route('admin.orders.index') }}"
           class="inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-brand-600">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Orders
        </a>
        <div class="text-xs text-slate-400">For phone / walk-in captures</div>
    </div>

    @if ($statusMessage)
        <x-admin.alert :type="$statusType">{{ $statusMessage }}</x-admin.alert>
    @endif

    <div class="rounded-xl border border-sky-200 bg-sky-50 p-4 text-xs text-sky-800">
        <strong class="text-sky-900">Manual order</strong> —
        for customers who called or walked in. Their order appears on the Kitchen Board like any other.
        Payment status defaults to <em>Pending</em> when paying cash — mark it paid after collection from
        the order detail page.
    </div>

    <form wire:submit="submit" class="space-y-6">

        {{-- Customer --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-sm font-bold text-slate-900">Customer</h2>
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-600">Who is this order for?</label>
                <select wire:model.live="customerId"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                    <option value="">Walk-in / Phone order (no name)</option>
                    @foreach ($customers as $c)
                        <option value="{{ $c->id }}">{{ $c->name }} · {{ $c->mobile }}</option>
                    @endforeach
                </select>
                @error('customerId') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                <p class="mt-1 text-[11px] text-slate-400">
                    Pick a registered customer to debit their wallet. Or leave as Walk-in for anonymous orders.
                </p>
            </div>
        </div>

        {{-- Delivery --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-sm font-bold text-slate-900">Delivery</h2>
            <div class="grid grid-cols-2 gap-2">
                <button type="button" wire:click="$set('deliveryMethod', 'pickup')"
                        @class([
                            'flex items-center justify-center gap-2 rounded-lg border-2 px-3 py-2.5 text-sm font-medium transition',
                            'border-brand-500 bg-brand-50 text-brand-700' => $deliveryMethod === 'pickup',
                            'border-slate-200 bg-white text-slate-600 hover:border-brand-300' => $deliveryMethod !== 'pickup',
                        ])>
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l9-9 9 9M5 10v10a1 1 0 001 1h3a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1h3a1 1 0 001-1V10"/>
                    </svg>
                    Pickup
                </button>
                <button type="button" wire:click="$set('deliveryMethod', 'delivery')"
                        @class([
                            'flex items-center justify-center gap-2 rounded-lg border-2 px-3 py-2.5 text-sm font-medium transition',
                            'border-brand-500 bg-brand-50 text-brand-700' => $deliveryMethod === 'delivery',
                            'border-slate-200 bg-white text-slate-600 hover:border-brand-300' => $deliveryMethod !== 'delivery',
                        ])>
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1"/>
                    </svg>
                    Delivery
                </button>
            </div>
        </div>

        {{-- Payment --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-sm font-bold text-slate-900">Payment</h2>
            <div class="grid grid-cols-2 gap-2">
                <button type="button" wire:click="$set('paymentMethod', 'cash')"
                        @class([
                            'rounded-lg border-2 px-3 py-2.5 text-left text-sm font-medium transition',
                            'border-brand-500 bg-brand-50 text-brand-700' => $paymentMethod === 'cash',
                            'border-slate-200 bg-white text-slate-600 hover:border-brand-300' => $paymentMethod !== 'cash',
                        ])>
                    <div class="font-semibold">Cash — paid externally</div>
                    <div class="text-[11px] text-slate-500">Order marked as <em>Pending payment</em></div>
                </button>
                <button type="button" wire:click="$set('paymentMethod', 'wallet')"
                        @disabled($customerId === '')
                        @class([
                            'rounded-lg border-2 px-3 py-2.5 text-left text-sm font-medium transition',
                            'border-brand-500 bg-brand-50 text-brand-700' => $paymentMethod === 'wallet',
                            'border-slate-200 bg-white text-slate-600 hover:border-brand-300' => $paymentMethod !== 'wallet',
                            'opacity-40 cursor-not-allowed' => $customerId === '',
                        ])>
                    <div class="font-semibold">Debit wallet</div>
                    <div class="text-[11px] text-slate-500">
                        @if ($customerId === '')
                            Choose a customer first
                        @else
                            Instant debit — marked <em>Paid</em>
                        @endif
                    </div>
                </button>
            </div>
            @error('paymentMethod') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>

        {{-- Items --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-sm font-bold text-slate-900">Items</h2>

            @if ($availableItems->isEmpty())
                <p class="py-6 text-center text-sm text-slate-400">
                    No items are published for today. Publish the menu first.
                </p>
            @else
                <div class="space-y-2">
                    @foreach ($availableItems as $item)
                        @php
                            $qty = (int) ($cart[$item->id] ?? 0);
                            $soldOut = $item->is_sold_out ?? false;
                            $limitReached = $item->is_limit_reached ?? false;
                            $blocked = $soldOut || $limitReached;
                        @endphp
                        <div @class([
                            'flex items-center gap-3 rounded-lg border p-3',
                            'border-slate-200 bg-white' => ! $blocked,
                            'border-slate-200 bg-slate-50 opacity-60' => $blocked,
                        ])>
                            <span class="text-base {{ $item->is_veg ? 'text-emerald-600' : 'text-rose-600' }}">●</span>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="truncate text-sm font-semibold text-slate-900">{{ $item->name }}</span>
                                    @if ($soldOut)
                                        <span class="rounded bg-rose-100 px-1.5 py-0.5 text-[10px] font-bold uppercase text-rose-700">Sold out</span>
                                    @elseif ($limitReached)
                                        <span class="rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-bold uppercase text-amber-800">Limit</span>
                                    @endif
                                </div>
                                <div class="text-xs text-slate-500">
                                    Nu. {{ number_format($item->price, 2) }}
                                    @if ($item->isMain()) · Main @else · Fast Food @endif
                                </div>
                            </div>

                            @if ($blocked)
                                <span class="shrink-0 text-[11px] font-semibold uppercase text-slate-400">Unavailable</span>
                            @else
                                <div class="inline-flex items-center overflow-hidden rounded-lg border border-brand-500 bg-brand-50">
                                    <button type="button" wire:click="decrementItem({{ $item->id }})"
                                            class="flex h-8 w-8 items-center justify-center text-brand-700 hover:bg-brand-100">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20 12H4"/>
                                        </svg>
                                    </button>
                                    <input type="number" min="0" max="50"
                                           value="{{ $qty }}"
                                           wire:change="setQuantity({{ $item->id }}, $event.target.value)"
                                           class="h-8 w-10 border-0 bg-transparent text-center text-sm font-bold text-brand-700 focus:outline-none focus:ring-0 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none" />
                                    <button type="button" wire:click="incrementItem({{ $item->id }})"
                                            class="flex h-8 w-8 items-center justify-center text-brand-700 hover:bg-brand-100">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                                        </svg>
                                    </button>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

            @error('cart') <p class="mt-2 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>

        {{-- Notes --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-sm font-bold text-slate-900">Notes</h2>
            <textarea wire:model="notes" rows="2"
                      placeholder="e.g. Mother called — order for daughter, arriving at counter 12:30"
                      class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"></textarea>
            @error('notes') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>

        {{-- Summary + Submit --}}
        @if ($cartItems->isNotEmpty())
            <div class="rounded-xl border-2 border-brand-300 bg-brand-50 p-4">
                <div class="mb-2 text-xs font-semibold uppercase tracking-wider text-brand-700">Order Summary</div>
                <ul class="space-y-1">
                    @foreach ($cartItems as $ci)
                        <li class="flex items-center justify-between text-sm">
                            <span class="text-slate-700">{{ $ci->name }} × {{ $ci->cart_qty }}</span>
                            <span class="font-medium text-slate-900">Nu. {{ number_format((float) $ci->price * $ci->cart_qty, 2) }}</span>
                        </li>
                    @endforeach
                </ul>
                <div class="mt-3 flex items-center justify-between border-t border-brand-200 pt-2 text-base font-bold text-brand-900">
                    <span>Total</span>
                    <span>Nu. {{ number_format($cartTotal, 2) }}</span>
                </div>
            </div>
        @endif

        <div class="flex items-center justify-end gap-3">
            <button type="button" wire:click="clearCart"
                    class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                Clear
            </button>
            <button type="submit"
                    wire:loading.attr="disabled" wire:target="submit"
                    class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-600 disabled:opacity-50">
                <span wire:loading.remove wire:target="submit">Create order</span>
                <span wire:loading wire:target="submit">Creating…</span>
            </button>
        </div>
    </form>
</div>