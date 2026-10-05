<div class="space-y-6">

    {{-- ─── KPI cards ──────────────────────────────────────── --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-admin.stat-card
            label="Today's Orders"
            :value="$stats['todayOrders']"
            icon="receipt"
            color="brand"
            :trend="$stats['readyCount'].' ready'"
            :trendUp="true" />

        <x-admin.stat-card
            label="Revenue Today"
            :value="'Nu. '.number_format($stats['todayRevenue'], 2)"
            icon="banknotes"
            color="emerald" />

        <x-admin.stat-card
            label="Pending Prep"
            :value="$stats['pendingPrep']"
            icon="cube"
            color="sky" />

        <x-admin.stat-card
            label="Items Available"
            :value="$stats['itemsAvailable']"
            icon="book"
            color="rose" />
    </div>

    {{-- ─── Alerts row ─────────────────────────────────────── --}}
    {{-- ─── Quick actions + alerts row ─────────────────────── --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">

        {{-- Kitchen Board — always visible --}}
        <a href="{{ route('admin.orders.kitchen') }}"
           class="group rounded-xl border-2 border-brand-200 bg-gradient-to-br from-brand-50 to-brand-100 p-4 shadow-sm transition hover:border-brand-400 hover:shadow-md">
            <div class="flex items-center gap-3">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-brand-500 text-white shadow-sm">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="text-lg font-bold text-brand-900">
                        {{ $stats['pendingPrep'] + $stats['readyCount'] }}
                    </div>
                    <div class="text-xs font-semibold text-brand-700">
                        Open Kitchen Board
                    </div>
                    <div class="text-[10px] text-brand-600">
                        @if ($stats['pendingPrep'] + $stats['readyCount'] === 0)
                            No active orders — all caught up
                        @else
                            {{ $stats['pendingPrep'] }} in prep · {{ $stats['readyCount'] }} ready
                        @endif
                    </div>
                </div>
                <svg class="h-5 w-5 shrink-0 text-brand-400 transition group-hover:translate-x-0.5 group-hover:text-brand-600"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </div>
        </a>

        {{-- Pending payment proofs --}}
        @if ($stats['pendingProofs'] > 0)
            <a href="{{ route('admin.payments.index') }}"
               class="group rounded-xl border border-amber-200 bg-amber-50 p-4 transition hover:border-amber-300 hover:shadow-md">
                <div class="flex items-center gap-3">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-700">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="text-lg font-bold text-amber-800">{{ $stats['pendingProofs'] }}</div>
                        <div class="text-xs text-amber-700">
                            Payment {{ $stats['pendingProofs'] === 1 ? 'proof' : 'proofs' }} pending
                        </div>
                    </div>
                    <svg class="h-5 w-5 shrink-0 text-amber-400 transition group-hover:translate-x-0.5 group-hover:text-amber-600"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </div>
            </a>
        @endif

        {{-- Low stock --}}
        @if ($stats['lowStockCount'] > 0)
            <a href="{{ route('admin.inventory.index', ['low' => 1]) }}"
               class="group rounded-xl border border-rose-200 bg-rose-50 p-4 transition hover:border-rose-300 hover:shadow-md">
                <div class="flex items-center gap-3">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-rose-100 text-rose-700">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="text-lg font-bold text-rose-800">{{ $stats['lowStockCount'] }}</div>
                        <div class="text-xs text-rose-700">
                            {{ $stats['lowStockCount'] === 1 ? 'Item' : 'Items' }} low on stock
                        </div>
                    </div>
                    <svg class="h-5 w-5 shrink-0 text-rose-400 transition group-hover:translate-x-0.5 group-hover:text-rose-600"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </div>
            </a>
        @endif
    </div>
    {{-- ─── Today's orders ─────────────────────────────────── --}}
    <div>
        <div class="mb-3 flex items-center justify-between">
            <h2 class="text-base font-bold text-slate-900">Today's Orders</h2>
            <span class="text-xs text-slate-500">{{ $stats['todayOrders'] }} total</span>
        </div>

        @if ($orders->isEmpty())
            <div class="rounded-xl border border-slate-200 bg-white p-8 text-center">
                <p class="text-sm text-slate-500">No orders yet today.</p>
            </div>
        @else
            <x-admin.data-table :headers="['Order', 'Customer', 'Items', 'Total', 'Status']">
                @foreach ($orders as $order)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-1.5">
                                <span class="rounded bg-brand-100 px-1.5 py-0.5 font-mono text-[10px] font-bold text-brand-700">{{ $order->display_ref }}</span>
                                <span class="font-mono text-[10px] text-slate-400">{{ $order->order_number }}</span>
                            </div>
                            <div class="text-xs text-slate-400">{{ $order->created_at->format('H:i') }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="font-medium text-slate-900">{{ $order->user->name }}</div>
                            <div class="text-xs text-slate-500">{{ $order->user->mobile }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-xs text-slate-600">
                                @foreach ($order->items as $item)
                                    <span class="{{ $item->is_veg ? 'text-emerald-600' : 'text-rose-600' }}">●</span>
                                    @if ($item->quantity > 1){{ $item->quantity }}× @endif{{ $item->item_name }}@if(!$loop->last)<span class="text-slate-400">, </span>@endif
                                @endforeach
                            </div>
                        </td>
                        <td class="px-4 py-3 font-semibold text-slate-900">
                            {{ config('app.currency', 'Nu.') }} {{ number_format($order->total, 2) }}
                        </td>
                        <td class="px-4 py-3">
                            <x-admin.badge :variant="$order->statusVariant()">
                                {{ $order->statusLabel() }}
                            </x-admin.badge>
                        </td>
                    </tr>
                @endforeach
            </x-admin.data-table>
        @endif
    </div>

    {{-- ─── Bottom row: Proofs + Low Stock + Low Balance ──── --}}
    <div class="grid gap-4 lg:grid-cols-3">

        {{-- Pending payment proofs --}}
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="mb-3 flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-900">Payment Proofs</h3>
                @if ($stats['pendingProofs'] > 0)
                    <x-admin.badge variant="warning">{{ $stats['pendingProofs'] }} pending</x-admin.badge>
                @endif
            </div>
            @if ($pendingProofs->isEmpty())
                <p class="py-4 text-center text-xs text-slate-400">No pending proofs.</p>
            @else
                <ul class="space-y-2">
                    @foreach ($pendingProofs as $proof)
                        <li class="flex items-center justify-between rounded-lg border border-slate-100 bg-slate-50 px-3 py-2">
                            <div class="min-w-0 flex-1">
                                <div class="truncate text-sm font-medium text-slate-800">{{ $proof->user->name }}</div>
                                <div class="text-xs text-slate-500">{{ $proof->bank_reference }}</div>
                            </div>
                            <div class="ml-2 text-sm font-semibold text-slate-900">
                                Nu. {{ number_format($proof->claimed_amount, 2) }}
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        {{-- Low stock --}}
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="mb-3 flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-900">Low Stock</h3>
                @if ($stats['lowStockCount'] > 0)
                    <x-admin.badge variant="danger">{{ $stats['lowStockCount'] }} items</x-admin.badge>
                @endif
            </div>
            @if ($lowStockItems->isEmpty())
                <p class="py-4 text-center text-xs text-slate-400">All stock is healthy.</p>
            @else
                <ul class="space-y-2">
                    @foreach ($lowStockItems as $item)
                        <li class="flex items-center justify-between rounded-lg border border-slate-100 bg-slate-50 px-3 py-2">
                            <div class="min-w-0 flex-1">
                                <div class="truncate text-sm font-medium text-slate-800">{{ $item->name }}</div>
                                <div class="text-xs text-slate-500">reorder at {{ $item->reorder_level }} {{ $item->unit }}</div>
                            </div>
                            <div class="ml-2 text-sm font-semibold text-rose-600">
                                {{ $item->displayStock() }}
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        {{-- Low wallet balance --}}
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="mb-3 flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-900">Low Wallet Balance</h3>
                @if ($stats['lowBalanceCount'] > 0)
                    <x-admin.badge variant="warning">{{ $stats['lowBalanceCount'] }} customers</x-admin.badge>
                @endif
            </div>
            @if ($lowBalanceCustomers->isEmpty())
                <p class="py-4 text-center text-xs text-slate-400">All customers have healthy balances.</p>
            @else
                <ul class="space-y-2">
                    @foreach ($lowBalanceCustomers as $customer)
                        <li class="flex items-center justify-between rounded-lg border border-slate-100 bg-slate-50 px-3 py-2">
                            <div class="min-w-0 flex-1">
                                <div class="truncate text-sm font-medium text-slate-800">{{ $customer->name }}</div>
                                <div class="text-xs text-slate-500">{{ $customer->mobile }}</div>
                            </div>
                            <div class="ml-2 text-sm font-semibold text-slate-600">
                                Nu. {{ number_format($customer->wallet_balance, 2) }}
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</div>