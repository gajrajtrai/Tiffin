<div class="mx-auto max-w-3xl px-4 py-6 sm:py-10">

    <div class="mb-6 flex items-end justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl">My Orders</h1>
            <p class="mt-1 text-sm text-slate-600">Your order history with {{ config('app.name') }}.</p>
        </div>
        <a href="{{ route('menu.index') }}"
           class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-600">
            Order again
        </a>
    </div>

    @if ($orders->isEmpty())
        <div class="rounded-xl border border-slate-200 bg-white p-12 text-center">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-brand-100 text-brand-600">
                <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
            </div>
            <h2 class="mt-4 text-base font-semibold text-slate-900">No orders yet</h2>
            <p class="mt-1 text-sm text-slate-500">Head to today's menu and place your first order.</p>
            <a href="{{ route('menu.index') }}"
               class="mt-4 inline-flex items-center gap-2 rounded-lg bg-brand-500 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-600">
                Browse menu
            </a>
        </div>
    @else
        <div class="space-y-3">
            @foreach ($orders as $order)
                <a href="{{ route('orders.show', $order) }}"
                   class="block rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-brand-300 hover:shadow-md">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-mono text-xs font-semibold text-slate-500">{{ $order->order_number }}</span>
								<x-admin.badge :variant="$order->statusVariant()">{{ $order->statusLabel() }}</x-admin.badge>
                                @if ($order->isDelivery())
                                    <x-admin.badge variant="info">Delivery</x-admin.badge>
                                @else
                                    <x-admin.badge variant="slate">Pickup</x-admin.badge>
                                @endif
                            </div>

                            <div class="mt-2 text-sm font-medium text-slate-900">
                                {{ $order->service_date->format('l, M j, Y') }}
                            </div>
                            <div class="mt-1 text-xs text-slate-500">
                                @foreach ($order->items as $item)
                                    @if ($item->quantity > 1){{ $item->quantity }}× @endif{{ $item->item_name }}@if (! $loop->last) · @endif
                                @endforeach
                            </div>
                        </div>

                        <div class="shrink-0 text-right">
                            <div class="text-base font-bold text-slate-900">Nu. {{ number_format($order->total, 2) }}</div>
                            <div class="mt-0.5 text-[10px] uppercase tracking-wider text-slate-400">Paid</div>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-6">{{ $orders->links() }}</div>
    @endif
</div>