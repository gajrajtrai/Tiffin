@use('App\Modules\Order\Models\Order')
<div class="space-y-4" wire:poll.15s>

    {{-- ─── Header row ─────────────────────────────────────── --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Kitchen Prep Board</h1>
            <p class="mt-1 text-sm text-slate-500">
                <span class="font-semibold text-slate-700">{{ now()->format('l, F j, Y') }}</span>
                · Auto-refreshes every 15 seconds
                <span class="ml-2 inline-flex items-center gap-1 text-xs text-emerald-600">
                    <span class="relative flex h-2 w-2">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                    </span>
                    Live
                </span>
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <div class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-center">
                <div class="text-2xl font-bold text-slate-900">{{ $stats['active'] }}</div>
                <div class="text-[10px] font-medium uppercase tracking-wider text-slate-500">Active</div>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-center">
                <div class="text-2xl font-bold text-emerald-600">{{ $stats['completed'] }}</div>
                <div class="text-[10px] font-medium uppercase tracking-wider text-slate-500">Completed</div>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-center">
                <div class="text-2xl font-bold text-slate-900">{{ $stats['totalToday'] }}</div>
                <div class="text-[10px] font-medium uppercase tracking-wider text-slate-500">Total Today</div>
            </div>
        </div>
    </div>

    @if ($statusMessage)
        <x-admin.alert :type="$statusType">{{ $statusMessage }}</x-admin.alert>
    @endif

    @if ($stats['active'] === 0)
        <div class="rounded-xl border border-slate-200 bg-white p-16 text-center">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h2 class="mt-4 text-lg font-bold text-slate-900">All caught up!</h2>
            <p class="mt-1 text-sm text-slate-500">
                No active orders. New orders will appear here automatically.
            </p>
        </div>
    @else
        {{-- ─── Status columns ─────────────────────────────── --}}
        <div class="grid gap-4 lg:grid-cols-4">

            @php
                $statusStyles = [
                    'pending'   => [
                        'header' => 'bg-amber-100 text-amber-900',
                        'border' => 'border-amber-200',
                        'ring'   => 'ring-amber-200',
                        'btn'    => 'bg-amber-600 hover:bg-amber-700',
                        'badge'  => 'bg-amber-100 text-amber-800',
                    ],
                    'confirmed' => [
                        'header' => 'bg-sky-100 text-sky-900',
                        'border' => 'border-sky-200',
                        'ring'   => 'ring-sky-200',
                        'btn'    => 'bg-sky-600 hover:bg-sky-700',
                        'badge'  => 'bg-sky-100 text-sky-800',
                    ],
                    'preparing' => [
                        'header' => 'bg-brand-100 text-brand-900',
                        'border' => 'border-brand-200',
                        'ring'   => 'ring-brand-200',
                        'btn'    => 'bg-brand-500 hover:bg-brand-600',
                        'badge'  => 'bg-brand-100 text-brand-800',
                    ],
                    'ready'     => [
                        'header' => 'bg-emerald-100 text-emerald-900',
                        'border' => 'border-emerald-200',
                        'ring'   => 'ring-emerald-200',
                        'btn'    => 'bg-emerald-600 hover:bg-emerald-700',
                        'badge'  => 'bg-emerald-100 text-emerald-800',
                    ],
                ];
            @endphp

            @foreach ($columns as $col)
                @php
                    $colOrders = $grouped[$col['status']] ?? collect();
                    $style = $statusStyles[$col['status']];
                @endphp

                <div class="flex flex-col rounded-xl border {{ $style['border'] }} bg-slate-50 shadow-sm">

                    {{-- Column header --}}
                    <div class="flex items-center justify-between rounded-t-xl {{ $style['header'] }} px-4 py-3">
                        <div>
                            <h2 class="text-sm font-bold">{{ $col['label'] }}</h2>
                            <p class="text-[10px] uppercase tracking-wider opacity-75">{{ $col['hint'] }}</p>
                        </div>
                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-white/40 text-sm font-bold">
                            {{ $colOrders->count() }}
                        </div>
                    </div>

                    {{-- Cards --}}
                    <div class="flex-1 space-y-3 p-3">
                        @forelse ($colOrders as $order)
                            @php
                                $nextStatus = match ($order->status) {
                                    Order::STATUS_PENDING   => Order::STATUS_CONFIRMED,
                                    Order::STATUS_CONFIRMED => Order::STATUS_PREPARING,
                                    Order::STATUS_PREPARING => Order::STATUS_READY,
                                    Order::STATUS_READY     => $order->isDelivery()
                                                                ? Order::STATUS_DELIVERED
                                                                : Order::STATUS_PICKED_UP,
                                    default => null,
                                };
                                $nextLabel = match ($order->status) {
                                    Order::STATUS_PENDING   => 'Confirm',
                                    Order::STATUS_CONFIRMED => 'Start preparing',
                                    Order::STATUS_PREPARING => 'Mark ready',
                                    Order::STATUS_READY     => $order->isDelivery() ? 'Mark delivered' : 'Mark picked up',
                                    default => null,
                                };
                            @endphp

                            <div class="rounded-lg border border-slate-200 bg-white shadow-sm transition hover:shadow-md">

                                {{-- Header --}}
                                <div class="flex items-center justify-between border-b border-slate-100 px-3 py-2">
                                    <div class="font-mono text-[11px] font-semibold text-slate-500">
                                        {{ $order->order_number }}
                                    </div>
                                    @if ($order->isDelivery())
                                        <span class="inline-flex items-center gap-1 rounded-full bg-sky-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-sky-800">
                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1"/>
                                            </svg>
                                            Delivery
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-slate-700">
                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l9-9 9 9M5 10v10a1 1 0 001 1h3a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1h3a1 1 0 001-1V10"/>
                                            </svg>
                                            Pickup
                                        </span>
                                    @endif
                                </div>

                                {{-- Body --}}
                                <div class="space-y-2 p-3">
                                    <div>
                                        <div class="text-xs font-semibold text-slate-500">Customer</div>
                                        <div class="text-sm font-bold text-slate-900">{{ $order->user->name }}</div>
                                        <div class="text-[11px] text-slate-500">{{ $order->user->mobile }}</div>
                                    </div>

                                    <div>
                                        <div class="text-xs font-semibold text-slate-500">Items</div>
                                        <ul class="mt-1 space-y-1">
                                            @foreach ($order->items as $item)
                                                <li class="flex items-start gap-2 text-sm text-slate-800">
                                                    <span class="{{ $item->is_veg ? 'text-emerald-600' : 'text-rose-600' }} mt-0.5">●</span>
                                                    <span class="flex-1 font-medium">{{ $item->item_name }}</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>

                                    <div class="flex items-center justify-between border-t border-slate-100 pt-2 text-[11px] text-slate-500">
                                        <span>Placed {{ $order->created_at->format('H:i') }}</span>
                                        <span class="font-semibold text-slate-700">Nu. {{ number_format($order->total, 0) }}</span>
                                    </div>
                                </div>

                                {{-- Action button --}}
                                @if ($nextStatus && auth()->user()->can('order.update-status'))
                                    <button type="button"
                                            wire:click="advance({{ $order->id }}, '{{ $nextStatus }}')"
                                            wire:loading.attr="disabled"
                                            wire:target="advance"
                                            class="w-full rounded-b-lg {{ $style['btn'] }} py-3 text-sm font-bold text-white transition disabled:opacity-50">
                                        {{ $nextLabel }} →
                                    </button>
                                @endif
                            </div>
                        @empty
                            <div class="flex h-32 items-center justify-center text-center">
                                <p class="text-xs text-slate-400">No orders in this stage.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>