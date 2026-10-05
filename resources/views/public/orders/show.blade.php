@use('App\Modules\Order\Models\Order')

<div class="mx-auto max-w-3xl space-y-5 px-4 py-6 sm:py-10">

    <a href="{{ route('orders.index') }}"
       class="inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-brand-600">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Back to my orders
    </a>

    @if ($editMessage)
        <div @class([
            'rounded-xl border p-3 text-sm',
            'border-emerald-200 bg-emerald-50 text-emerald-800' => $editMessageType === 'success',
            'border-rose-200 bg-rose-50 text-rose-800' => $editMessageType === 'error',
        ])>
            {{ $editMessage }}
        </div>
    @endif
    {{-- Success banner right after placing --}}
    @if (session('order_just_placed'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
            <div class="flex items-start gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-emerald-500 text-white">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <div>
                    <div class="text-sm font-bold text-emerald-900">Order placed!</div>
                    <div class="mt-0.5 text-xs text-emerald-700">
                        Thank you for ordering with us. We will do our best to serve you a fresh and savory meal.
                        We'll have it ready for
                        {{ $order->isDelivery() ? 'delivery to the college gate' : 'pickup at our counter' }} today.
                        Looking forward to your next order.
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Header card --}}
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-center gap-2">
            <span class="rounded-md bg-brand-100 px-2 py-0.5 font-mono text-sm font-bold text-brand-700">{{ $order->display_ref }}</span>
            <span class="font-mono text-xs text-slate-400">{{ $order->order_number }}</span>
            <x-admin.badge :variant="$order->statusVariant()">{{ $order->statusLabel() }}</x-admin.badge>
            @if ($order->isDelivery())
                <x-admin.badge variant="info">Delivery</x-admin.badge>
            @else
                <x-admin.badge variant="slate">Pickup</x-admin.badge>
            @endif
            <x-admin.badge variant="success">Paid from wallet</x-admin.badge>
        </div>

        <div class="mt-3 text-lg font-bold text-slate-900">
            {{ $order->service_date->format('l, F j, Y') }}
        </div>
        @if ($order->delivery_slot)
            <div class="mt-1 text-sm text-slate-600">
                Slot: <strong>{{ $order->delivery_slot }}</strong>
            </div>
        @endif
		        @if ($order->isEditable())
            <div class="mt-4 rounded-lg border border-slate-200 bg-slate-50 p-3">
                <div class="mb-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Delivery method</div>
                <div class="grid grid-cols-2 gap-2">
                    <button type="button" wire:click="changeDeliveryMethod('delivery')"
                            wire:loading.attr="disabled"
                            @class([
                                'flex items-center justify-center gap-2 rounded-lg border-2 px-3 py-2 text-sm font-medium transition',
                                'border-brand-500 bg-white text-brand-700' => $order->isDelivery(),
                                'border-slate-200 bg-white text-slate-600 hover:border-brand-300' => ! $order->isDelivery(),
                            ])>
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1"/>
                        </svg>
                        Delivery
                    </button>
                    <button type="button" wire:click="changeDeliveryMethod('pickup')"
                            wire:loading.attr="disabled"
                            @class([
                                'flex items-center justify-center gap-2 rounded-lg border-2 px-3 py-2 text-sm font-medium transition',
                                'border-brand-500 bg-white text-brand-700' => $order->isPickup(),
                                'border-slate-200 bg-white text-slate-600 hover:border-brand-300' => ! $order->isPickup(),
                            ])>
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l9-9 9 9M5 10v10a1 1 0 001 1h3a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1h3a1 1 0 001-1V10"/>
                        </svg>
                        Pickup
                    </button>
                </div>
            </div>
        @endif
        <div class="mt-1 text-xs text-slate-400">
            Placed {{ $order->created_at->format('M j, Y \a\t H:i') }}
        </div>
        {{-- Edit window banner --}}
        @if ($order->isEditable())
            <div class="mt-4 rounded-lg border border-sky-200 bg-sky-50 p-3">
                <div class="flex items-start gap-3">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-sky-100 text-sky-700">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="text-sm font-semibold text-sky-900">You can still edit this order</div>
                        <div class="text-xs text-sky-700">
                            <span x-data="{
                                until: '{{ $order->editable_until->toIso8601String() }}',
                                remaining: {{ $order->editSecondsRemaining() }},
                                init() {
                                    const tick = () => {
                                        this.remaining = Math.max(0, Math.floor((new Date(this.until) - new Date()) / 1000));
                                    };
                                    setInterval(tick, 1000);
                                },
                                format() {
                                    const m = Math.floor(this.remaining / 60);
                                    const s = this.remaining % 60;
                                    return m + ':' + String(s).padStart(2, '0');
                                }
                            }" x-text="format()"></span>
                            remaining to add items, remove items, or change delivery method.
                        </div>
                    </div>
                </div>
            </div>
        @endif
        @if ($order->isCancelled())
            <div class="mt-4 rounded-lg border border-rose-200 bg-rose-50 p-3">
                <div class="text-xs font-semibold uppercase tracking-wider text-rose-800">
                    Cancelled {{ $order->cancelled_at?->format('M j, H:i') }}
                </div>
                @if ($order->cancelled_reason)
                    <div class="mt-1 text-sm text-rose-700">{{ $order->cancelled_reason }}</div>
                @endif
                <div class="mt-2 text-xs text-rose-700">
                    Refund of Nu. {{ number_format($order->total, 2) }} has been credited to your wallet.
                </div>
            </div>
        @endif
    </div>

    {{-- Items --}}
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-4 py-3">
            <h2 class="text-sm font-bold text-slate-900">Your items</h2>
        </div>
        <div class="divide-y divide-slate-100">
            @foreach ($order->items as $item)
                <div class="flex items-center gap-3 px-4 py-3">
                    <span class="text-base {{ $item->is_veg ? 'text-emerald-600' : 'text-rose-600' }}">●</span>
                    <div class="min-w-0 flex-1">
                        <div class="truncate text-sm font-medium text-slate-900">
                            {{ $item->item_name }}
                            @if ($item->quantity > 1)
                                <span class="ml-1 text-xs font-bold text-brand-600">× {{ $item->quantity }}</span>
                            @endif
                        </div>
                        <div class="text-xs text-slate-500">
                            {{ $item->isMain() ? 'Main Course' : 'Fast Food' }}
                            @if ($item->quantity > 1)
                                · Nu. {{ number_format($item->item_price, 2) }} each
                            @endif
                        </div>
                    </div>
                    <div class="shrink-0 text-sm font-semibold text-slate-900">
                        Nu. {{ number_format($item->lineTotal(), 2) }}
                    </div>
                    @if ($order->isEditable())
                        <button type="button"
                                wire:click="removeItem({{ $item->id }})"
                                wire:confirm="Remove {{ $item->item_name }}? The amount will be refunded to your wallet."
                                wire:loading.attr="disabled"
                                class="shrink-0 rounded-md p-1.5 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600"
                                title="Remove">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    @endif
                </div>
            @endforeach
        </div>
        <div class="flex items-center justify-between border-t border-slate-200 bg-slate-50 px-4 py-3">
            <span class="text-sm font-semibold text-slate-700">Total charged</span>
            <span class="text-lg font-bold text-slate-900">Nu. {{ number_format($order->total, 2) }}</span>
        </div>

        @if ($order->isEditable() && $availableItems->isNotEmpty())
            <div class="border-t border-slate-200 p-3">
                <button type="button"
                        wire:click="openAddItems"
                        wire:loading.attr="disabled"
                        class="w-full rounded-lg border-2 border-dashed border-brand-300 bg-brand-50 py-2.5 text-sm font-semibold text-brand-700 transition hover:border-brand-500 hover:bg-brand-100">
                    <span class="inline-flex items-center gap-2">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                        </svg>
                        Add more items
                    </span>
                </button>
            </div>
        @endif
    </div>

    {{-- Wallet transaction --}}
    @if ($order->walletTransaction)
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <h2 class="text-xs font-semibold uppercase tracking-wider text-slate-500">Wallet</h2>
            <div class="mt-3 space-y-2 text-sm">
                <div class="flex items-center justify-between">
                    <span class="text-slate-600">Debited from wallet</span>
                    <span class="font-medium text-rose-600">− Nu. {{ number_format($order->walletTransaction->amount, 2) }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-600">Balance after</span>
                    <span class="font-semibold text-slate-900">Nu. {{ number_format($order->walletTransaction->balance_after, 2) }}</span>
                </div>
            </div>
            <a href="{{ route('wallet.index') }}"
               class="mt-3 inline-flex items-center gap-1 text-xs font-medium text-brand-600 hover:text-brand-700">
                View wallet
                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>
    @endif

    {{-- Actions --}}
    <div class="flex flex-wrap items-center gap-3">
        <a href="{{ route('menu.index') }}"
           class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-600">
            Order again
        </a>
        <a href="{{ route('orders.index') }}"
           class="rounded-lg border border-slate-300 bg-white px-5 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
            All my orders
        </a>
    </div>
	    {{-- Add-items modal --}}
    <x-admin.modal name="add-items" title="Add to your order" maxWidth="lg">
        @if ($showAddItemsModal)
            <div class="space-y-4">
                <p class="text-xs text-slate-500">
                    Items are added instantly and debited from your wallet. You can remove them until the edit window closes.
                </p>

                @if ($availableItems->isEmpty())
                    <p class="py-8 text-center text-sm text-slate-400">No items are available right now.</p>
                @else
                    <div class="max-h-96 space-y-2 overflow-y-auto pr-1">
                        @foreach ($availableItems as $item)
                            @php
                                $isSoldOut = $item->is_sold_out ?? false;
                                $existingQty = $order->items->firstWhere('menu_item_id', $item->id)?->quantity ?? 0;
                            @endphp
                            <div @class([
                                'flex items-center gap-3 rounded-lg border p-3',
                                'border-slate-200 bg-white' => ! $isSoldOut,
                                'border-slate-200 bg-slate-50 opacity-60' => $isSoldOut,
                            ])>
                                <span class="text-base {{ $item->is_veg ? 'text-emerald-600' : 'text-rose-600' }}">●</span>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <span class="truncate text-sm font-semibold text-slate-900">{{ $item->name }}</span>
                                        @if ($existingQty > 0)
                                            <span class="rounded bg-brand-100 px-1.5 py-0.5 text-[10px] font-bold text-brand-700">
                                                already {{ $existingQty }}×
                                            </span>
                                        @endif
                                    </div>
                                    <div class="text-xs text-slate-500">
                                        {{ $item->isMain() ? 'Main Course' : 'Fast Food' }} · Nu. {{ number_format($item->price, 2) }}
                                    </div>
                                </div>
                                @if ($isSoldOut)
                                    <span class="shrink-0 rounded-lg bg-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-500">
                                        Sold out
                                    </span>
                                @else
                                    <button type="button"
                                            wire:click="addItem({{ $item->id }})"
                                            wire:loading.attr="disabled"
                                            wire:target="addItem({{ $item->id }})"
                                            class="shrink-0 rounded-lg bg-brand-500 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-brand-600 disabled:opacity-50">
                                        <span wire:loading.remove wire:target="addItem({{ $item->id }})">Add</span>
                                        <span wire:loading wire:target="addItem({{ $item->id }})">…</span>
                                    </button>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <x-slot:footer>
                <button type="button" wire:click="closeAddItems"
                        class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    Done
                </button>
            </x-slot:footer>
        @endif
    </x-admin.modal>
</div>