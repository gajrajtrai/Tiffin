@use('App\Modules\Order\Models\Order')

<div class="mx-auto max-w-3xl space-y-5 px-4 py-6 sm:py-10">

    <a href="{{ route('orders.index') }}"
       class="inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-brand-600">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Back to my orders
    </a>

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
                        We've received your order and it will be prepared for
                        {{ $order->isDelivery() ? 'delivery to the college gate' : 'pickup at our counter' }} today.
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Header card --}}
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-center gap-2">
            <span class="font-mono text-sm font-semibold text-slate-500">{{ $order->order_number }}</span>
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
        <div class="mt-1 text-xs text-slate-400">
            Placed {{ $order->created_at->format('M j, Y \a\t H:i') }}
        </div>

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
                </div>
            @endforeach
        </div>
        <div class="flex items-center justify-between border-t border-slate-200 bg-slate-50 px-4 py-3">
            <span class="text-sm font-semibold text-slate-700">Total charged</span>
            <span class="text-lg font-bold text-slate-900">Nu. {{ number_format($order->total, 2) }}</span>
        </div>
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
</div>