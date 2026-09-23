@php
    // Precompute chart scaling
    $maxRevenue = collect($dailyPoints)->max('revenue') ?: 1;
@endphp

<div class="space-y-6">

    {{-- ─── Page header + date range ───────────────────────── --}}
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Reports</h1>
            <p class="mt-1 text-sm text-slate-500">
                From <strong>{{ $fromDate->format('M j, Y') }}</strong> to <strong>{{ $toDate->format('M j, Y') }}</strong>
                · {{ (int) round(abs($fromDate->diffInDays($toDate))) }} {{ (int) round(abs($fromDate->diffInDays($toDate))) === 1 ? 'day' : 'days' }}
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <button type="button" wire:click="presetToday"
                    class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">
                Today
            </button>
            <button type="button" wire:click="presetThisWeek"
                    class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">
                This week
            </button>
            <button type="button" wire:click="presetThisMonth"
                    class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">
                This month
            </button>
            <button type="button" wire:click="presetLastMonth"
                    class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">
                Last month
            </button>
            <button type="button" wire:click="presetLast30Days"
                    class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">
                Last 30 days
            </button>
        </div>
    </div>

    {{-- ─── Custom date range ──────────────────────────────── --}}
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid gap-3 sm:grid-cols-12">
            <div class="sm:col-span-4">
                <label class="mb-1 block text-xs font-medium text-slate-600">From</label>
                <input type="date" wire:model.live="from"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
            </div>
            <div class="sm:col-span-4">
                <label class="mb-1 block text-xs font-medium text-slate-600">To</label>
                <input type="date" wire:model.live="to"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
            </div>
            <div class="flex items-end sm:col-span-4">
                @can('report.export')
                    <div class="grid w-full grid-cols-2 gap-2">
                        <a href="{{ route('admin.reports.export', ['type' => 'orders', 'from' => $fromDate->toDateString(), 'to' => $toDate->toDateString()]) }}"
                           class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-center text-xs font-medium text-slate-700 hover:bg-slate-50">
                            Orders CSV
                        </a>
                        <a href="{{ route('admin.reports.export', ['type' => 'items', 'from' => $fromDate->toDateString(), 'to' => $toDate->toDateString()]) }}"
                           class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-center text-xs font-medium text-slate-700 hover:bg-slate-50">
                            Items CSV
                        </a>
                        <a href="{{ route('admin.reports.export', ['type' => 'customers', 'from' => $fromDate->toDateString(), 'to' => $toDate->toDateString()]) }}"
                           class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-center text-xs font-medium text-slate-700 hover:bg-slate-50">
                            Customers CSV
                        </a>
                        <a href="{{ route('admin.reports.export', ['type' => 'expenses', 'from' => $fromDate->toDateString(), 'to' => $toDate->toDateString()]) }}"
                           class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-center text-xs font-medium text-slate-700 hover:bg-slate-50">
                            Expenses CSV
                        </a>
                    </div>
                @endcan
            </div>
        </div>
    </div>

    {{-- ─── Headline stats ─────────────────────────────────── --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <x-admin.stat-card label="Revenue" :value="'Nu. '.number_format($revenue, 0)" icon="banknotes" color="brand" />
        <x-admin.stat-card label="Orders" :value="$orderCount" icon="receipt" color="sky" />
        <x-admin.stat-card label="Avg Order" :value="'Nu. '.number_format($avgOrderValue, 0)" icon="chart-bar" color="emerald" />
        <x-admin.stat-card label="Net Profit" :value="'Nu. '.number_format($netProfit, 0)" icon="banknotes" :color="$netProfit >= 0 ? 'emerald' : 'rose'" />
    </div>

    {{-- ─── Secondary stats ────────────────────────────────── --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-medium uppercase tracking-wider text-slate-500">Expenses</div>
            <div class="mt-1 text-xl font-bold text-rose-600">Nu. {{ number_format($expenseTotal, 0) }}</div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-medium uppercase tracking-wider text-slate-500">Active Customers</div>
            <div class="mt-1 text-xl font-bold text-slate-900">{{ $activeCustomers }}</div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-medium uppercase tracking-wider text-slate-500">Delivery / Pickup</div>
            <div class="mt-1 text-xl font-bold text-slate-900">{{ $deliveryCount }} / {{ $pickupCount }}</div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-medium uppercase tracking-wider text-slate-500">Cancelled</div>
            <div class="mt-1 text-xl font-bold text-slate-900">{{ $cancelledCount }}</div>
        </div>
    </div>

    {{-- ─── Daily sales chart (inline SVG bar chart) ───────── --}}
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-900">Daily Revenue</h2>
            <div class="text-xs text-slate-500">
                Peak: Nu. {{ number_format($maxRevenue, 0) }}
            </div>
        </div>

        @if (count($dailyPoints) === 0 || $maxRevenue <= 0)
            <div class="p-8 text-center text-sm text-slate-400">No sales in this range.</div>
        @else
            <div class="overflow-x-auto">
                <div class="flex h-40 min-w-full items-end gap-1">
                    @foreach ($dailyPoints as $point)
                        @php
                            $heightPct = $maxRevenue > 0 ? max(2, ($point['revenue'] / $maxRevenue) * 100) : 2;
                        @endphp
                        <div class="group relative flex-1" style="min-width: 14px;">
                            <div class="flex h-40 items-end">
                                <div class="w-full rounded-t bg-brand-500/70 transition group-hover:bg-brand-600"
                                     style="height: {{ $heightPct }}%;"
                                     title="{{ $point['label'] }}: Nu. {{ number_format($point['revenue'], 0) }} ({{ $point['orders'] }} orders)">
                                </div>
                            </div>
                            {{-- Tooltip on hover --}}
                            <div class="pointer-events-none absolute bottom-full left-1/2 z-10 mb-1 hidden -translate-x-1/2 whitespace-nowrap rounded bg-slate-900 px-2 py-1 text-[10px] font-medium text-white group-hover:block">
                                {{ $point['label'] }} · Nu. {{ number_format($point['revenue'], 0) }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="mt-2 flex justify-between text-[10px] text-slate-400">
                <span>{{ $dailyPoints[0]['label'] }}</span>
                <span>{{ $dailyPoints[count($dailyPoints) - 1]['label'] }}</span>
            </div>
        @endif
    </div>

    {{-- ─── Two-column: Top items + Top customers ─────────── --}}
    <div class="grid gap-6 lg:grid-cols-2">

        {{-- Top items --}}
        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-4 py-3">
                <h2 class="text-sm font-bold text-slate-900">Top 10 Items</h2>
                <p class="mt-0.5 text-xs text-slate-500">By quantity ordered</p>
            </div>

            @if ($topItems->isEmpty())
                <div class="p-8 text-center text-sm text-slate-400">No item sales in this range.</div>
            @else
                <div class="divide-y divide-slate-100">
                    @foreach ($topItems as $index => $item)
                        <div class="flex items-center gap-3 px-4 py-3">
                            <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-slate-600">
                                {{ $index + 1 }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="truncate text-sm font-medium text-slate-900">{{ $item->item_name }}</span>
                                    <span class="{{ $item->is_veg ? 'text-emerald-600' : 'text-rose-600' }}">●</span>
                                </div>
                                <div class="text-xs text-slate-500">
                                    {{ $item->item_type === 'main' ? 'Main' : 'Fast Food' }}
                                    · {{ $item->total_quantity ?? $item->order_count }} sold
                                    · {{ $item->order_count }} {{ $item->order_count === 1 ? 'order' : 'orders' }}
                                </div>
                            </div>
                            <div class="shrink-0 text-right text-sm font-semibold text-slate-900">
                                Nu. {{ number_format($item->revenue, 0) }}
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Top customers --}}
        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-4 py-3">
                <h2 class="text-sm font-bold text-slate-900">Top 10 Customers</h2>
                <p class="mt-0.5 text-xs text-slate-500">By total spend in range</p>
            </div>

            @if ($topCustomers->isEmpty())
                <div class="p-8 text-center text-sm text-slate-400">No customer activity in this range.</div>
            @else
                <div class="divide-y divide-slate-100">
                    @foreach ($topCustomers as $index => $cust)
                        <div class="flex items-center gap-3 px-4 py-3">
                            <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-slate-600">
                                {{ $index + 1 }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="truncate text-sm font-medium text-slate-900">{{ $cust->name }}</div>
                                <div class="text-xs text-slate-500">
                                    {{ $cust->mobile }} · {{ $cust->order_count }} orders
                                </div>
                            </div>
                            <div class="shrink-0 text-right">
                                <div class="text-sm font-semibold text-slate-900">Nu. {{ number_format($cust->total_spend, 0) }}</div>
                                <div class="text-[10px] text-slate-400">wallet Nu. {{ number_format($cust->wallet_balance, 0) }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- ─── Two-column: Expense breakdown + Wallet activity ── --}}
    <div class="grid gap-6 lg:grid-cols-2">

        {{-- Expense breakdown --}}
        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-4 py-3">
                <h2 class="text-sm font-bold text-slate-900">Expense Breakdown</h2>
                <p class="mt-0.5 text-xs text-slate-500">By category</p>
            </div>

            @if ($expenseBreakdown->isEmpty())
                <div class="p-8 text-center text-sm text-slate-400">No expenses in this range.</div>
            @else
                <div class="space-y-3 p-4">
                    @foreach ($expenseBreakdown as $cat)
                        @php
                            $pct = $expenseTotal > 0 ? ($cat->total / $expenseTotal) * 100 : 0;
                        @endphp
                        <div>
                            <div class="mb-1 flex items-center justify-between text-sm">
                                <span class="font-medium text-slate-800">{{ $cat->name }}</span>
                                <span class="font-semibold text-slate-900">Nu. {{ number_format($cat->total, 0) }}</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full bg-brand-500" style="width: {{ $pct }}%;"></div>
                            </div>
                            <div class="mt-0.5 text-right text-[10px] text-slate-400">
                                {{ number_format($pct, 1) }}% of total
                            </div>
                        </div>
                    @endforeach

                    <div class="flex items-center justify-between border-t-2 border-slate-200 pt-3 text-sm">
                        <span class="font-semibold text-slate-700">Total Expenses</span>
                        <span class="font-bold text-rose-600">Nu. {{ number_format($expenseTotal, 0) }}</span>
                    </div>
                </div>
            @endif
        </div>

        {{-- Wallet activity --}}
        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-4 py-3">
                <h2 class="text-sm font-bold text-slate-900">Wallet Activity</h2>
                <p class="mt-0.5 text-xs text-slate-500">Money in, out, and float</p>
            </div>

            <div class="space-y-3 p-4">
                <div class="flex items-center justify-between rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2">
                    <div>
                        <div class="text-xs font-medium uppercase tracking-wider text-emerald-700">Credits</div>
                        <div class="text-[10px] text-emerald-600">Top-ups approved</div>
                    </div>
                    <div class="text-lg font-bold text-emerald-700">+ Nu. {{ number_format($walletCredits, 0) }}</div>
                </div>

                <div class="flex items-center justify-between rounded-lg border border-rose-200 bg-rose-50 px-3 py-2">
                    <div>
                        <div class="text-xs font-medium uppercase tracking-wider text-rose-700">Debits</div>
                        <div class="text-[10px] text-rose-600">Spent on orders</div>
                    </div>
                    <div class="text-lg font-bold text-rose-700">− Nu. {{ number_format($walletDebits, 0) }}</div>
                </div>

                @if ($walletRefunds > 0)
                    <div class="flex items-center justify-between rounded-lg border border-sky-200 bg-sky-50 px-3 py-2">
                        <div>
                            <div class="text-xs font-medium uppercase tracking-wider text-sky-700">Refunds</div>
                            <div class="text-[10px] text-sky-600">Order cancellations</div>
                        </div>
                        <div class="text-lg font-bold text-sky-700">+ Nu. {{ number_format($walletRefunds, 0) }}</div>
                    </div>
                @endif

                <div class="flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                    <div>
                        <div class="text-xs font-medium uppercase tracking-wider text-slate-700">Customer Wallet Float</div>
                        <div class="text-[10px] text-slate-500">Total balance held across all customers (as of now)</div>
                    </div>
                    <div class="text-lg font-bold text-slate-900">Nu. {{ number_format($walletFloat, 0) }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ─── Stock valuation ────────────────────────────────── --}}
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-4 py-3">
            <h2 class="text-sm font-bold text-slate-900">Inventory Snapshot</h2>
            <p class="mt-0.5 text-xs text-slate-500">Current stock valuation (independent of date range)</p>
        </div>

        <div class="grid gap-4 p-4 sm:grid-cols-4">
            <div>
                <div class="text-xs font-medium uppercase tracking-wider text-slate-500">Total Stock Value</div>
                <div class="mt-1 text-2xl font-bold text-brand-600">Nu. {{ number_format($stockStats['totalValue'], 0) }}</div>
            </div>
            <div>
                <div class="text-xs font-medium uppercase tracking-wider text-slate-500">Items Tracked</div>
                <div class="mt-1 text-2xl font-bold text-slate-900">{{ $stockStats['totalItems'] }}</div>
            </div>
            <div>
                <div class="text-xs font-medium uppercase tracking-wider text-slate-500">Low Stock</div>
                <div class="mt-1 text-2xl font-bold {{ $stockStats['lowStock'] > 0 ? 'text-amber-600' : 'text-slate-900' }}">
                    {{ $stockStats['lowStock'] }}
                </div>
            </div>
            <div>
                <div class="text-xs font-medium uppercase tracking-wider text-slate-500">Out of Stock</div>
                <div class="mt-1 text-2xl font-bold {{ $stockStats['outOfStock'] > 0 ? 'text-rose-600' : 'text-slate-900' }}">
                    {{ $stockStats['outOfStock'] }}
                </div>
            </div>
        </div>
    </div>
</div>