@use('App\Modules\Order\Models\Order')

<div class="space-y-4" wire:poll.5s.visible>

    {{-- ─── Header row ─────────────────────────────────────── --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Kitchen Prep Board</h1>
            <p class="mt-1 text-sm text-slate-500">
                <span class="font-semibold text-slate-700">{{ now()->format('l, F j, Y') }}</span>
                · Auto-refreshes every 5 seconds (paused when tab is hidden)
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
                <div class="text-2xl font-bold text-rose-600">{{ $stats['rejected'] }}</div>
                <div class="text-[10px] font-medium uppercase tracking-wider text-slate-500">Rejected</div>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-center">
                <div class="text-2xl font-bold text-slate-900">{{ $stats['totalToday'] }}</div>
                <div class="text-[10px] font-medium uppercase tracking-wider text-slate-500">Total</div>
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
        @php
            $colStyles = [
                'new' => [
                    'header'   => 'bg-amber-100 text-amber-900',
                    'border'   => 'border-amber-200',
                    'btn'      => 'bg-amber-600 hover:bg-amber-700',
                    'btnAlt'   => 'border-amber-300 text-amber-800 hover:bg-amber-100',
                ],
                'preparing' => [
                    'header'   => 'bg-brand-100 text-brand-900',
                    'border'   => 'border-brand-200',
                    'btn'      => 'bg-brand-500 hover:bg-brand-600',
                    'btnAlt'   => 'border-brand-300 text-brand-800 hover:bg-brand-100',
                ],
                'ready' => [
                    'header'   => 'bg-emerald-100 text-emerald-900',
                    'border'   => 'border-emerald-200',
                    'btn'      => 'bg-emerald-600 hover:bg-emerald-700',
                    'btnAlt'   => 'border-emerald-300 text-emerald-800 hover:bg-emerald-100',
                ],
            ];
        @endphp

        {{-- ─── Prep summary — item-wise totals for today ──── --}}
        @if ($prepMains->isNotEmpty() || $prepFastFood->isNotEmpty())
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Today's Prep Summary</h2>
                        <p class="mt-0.5 text-xs text-slate-500">
                            To Be prepared list — excludes delivered, picked up, and cancelled
                        </p>
                    </div>
                    <div class="text-right text-xs text-slate-500">
                        {{ now()->format('g:i A') }}
                    </div>
                </div>

                <div class="grid gap-0 sm:grid-cols-2 sm:divide-x sm:divide-slate-200">

                    {{-- Mains --}}
                    <div class="p-4">
                        <div class="mb-3 flex items-center gap-2">
                            <span class="inline-flex h-5 w-5 items-center justify-center rounded bg-brand-100 text-[10px] font-bold text-brand-700">M</span>
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-600">Main Courses</h3>
                            @if ($prepMains->isEmpty())
                                <span class="ml-auto text-[10px] text-slate-400">—</span>
                            @else
                                <span class="ml-auto text-[10px] font-semibold text-slate-500">
                                    {{ $prepMains->sum('total_qty') }} total
                                </span>
                            @endif
                        </div>

                        @if ($prepMains->isEmpty())
                            <p class="text-xs text-slate-400">No mains ordered today.</p>
                        @else
                            <ul class="space-y-2">
                                @foreach ($prepMains as $row)
                                    <li wire:key="prep-m-{{ $row->menu_item_id }}" class="flex items-center gap-3">
                                        <span class="{{ $row->is_veg ? 'text-emerald-600' : 'text-rose-600' }}">●</span>
                                        <span class="min-w-0 flex-1 truncate text-sm font-medium text-slate-800">
                                            {{ $row->item_name }}
                                        </span>

                                        @if ($row->is_sold_out)
                                            <span class="rounded bg-rose-100 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wider text-rose-700">
                                                Sold out
                                            </span>
                                        @endif

                                        <span class="inline-flex min-w-[2.5rem] justify-center rounded-md bg-brand-500 px-2 py-0.5 text-sm font-bold text-white"
                                              @if ($row->daily_limit) title="{{ $row->ordered_total_today }} of {{ $row->daily_limit }} sold today" @endif>
                                            {{ (int) $row->total_qty }}
                                        </span>
                                        @if ($row->daily_limit)
                                            <span class="text-[10px] text-slate-400">
                                                {{ $row->ordered_total_today }}/{{ $row->daily_limit }}
                                            </span>
                                        @endif

                                        @can('menu.publish')
                                            @if ($row->is_sold_out)
                                                <button type="button"
                                                        wire:click="toggleSoldOut({{ $row->menu_item_id }})"
                                                        wire:loading.attr="disabled"
                                                        class="rounded-md border border-emerald-300 bg-white px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-emerald-700 transition hover:bg-emerald-50"
                                                        title="Make available again">
                                                    Restock
                                                </button>
                                            @else
                                                <button type="button"
                                                        wire:click="toggleSoldOut({{ $row->menu_item_id }})"
                                                        wire:confirm="Mark {{ $row->item_name }} as sold out? New orders will be blocked."
                                                        wire:loading.attr="disabled"
                                                        class="rounded-md border border-slate-300 bg-white px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-slate-600 transition hover:bg-rose-50 hover:border-rose-300 hover:text-rose-700"
                                                        title="Mark sold out">
                                                    Sold out
                                                </button>
                                            @endif
                                        @endcan
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>

                    {{-- Fast Food --}}
                    <div class="border-t border-slate-200 p-4 sm:border-t-0">
                        <div class="mb-3 flex items-center gap-2">
                            <span class="inline-flex h-5 w-5 items-center justify-center rounded bg-sky-100 text-[10px] font-bold text-sky-700">F</span>
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-600">Fast Food</h3>
                            @if ($prepFastFood->isEmpty())
                                <span class="ml-auto text-[10px] text-slate-400">—</span>
                            @else
                                <span class="ml-auto text-[10px] font-semibold text-slate-500">
                                    {{ $prepFastFood->sum('total_qty') }} total
                                </span>
                            @endif
                        </div>

                        @if ($prepFastFood->isEmpty())
                            <p class="text-xs text-slate-400">No fast food ordered today.</p>
                        @else
                            <ul class="space-y-2">
                                @foreach ($prepFastFood as $row)
                                    <li wire:key="prep-f-{{ $row->menu_item_id }}" class="flex items-center gap-3">
                                        <span class="{{ $row->is_veg ? 'text-emerald-600' : 'text-rose-600' }}">●</span>
                                        <span class="min-w-0 flex-1 truncate text-sm font-medium text-slate-800">
                                            {{ $row->item_name }}
                                        </span>

                                        @if ($row->is_sold_out)
                                            <span class="rounded bg-rose-100 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wider text-rose-700">
                                                Sold out
                                            </span>
                                        @endif

                                        <span class="inline-flex min-w-[2.5rem] justify-center rounded-md bg-brand-500 px-2 py-0.5 text-sm font-bold text-white"
                                              @if ($row->daily_limit) title="{{ $row->ordered_total_today }} of {{ $row->daily_limit }} sold today" @endif>
                                            {{ (int) $row->total_qty }}
                                        </span>
                                        @if ($row->daily_limit)
                                            <span class="text-[10px] text-slate-400">
                                                {{ $row->ordered_total_today }}/{{ $row->daily_limit }}
                                            </span>
                                        @endif
                                        @can('menu.publish')
                                            @if ($row->is_sold_out)
                                                <button type="button"
                                                        wire:click="toggleSoldOut({{ $row->menu_item_id }})"
                                                        wire:loading.attr="disabled"
                                                        class="rounded-md border border-emerald-300 bg-white px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-emerald-700 transition hover:bg-emerald-50"
                                                        title="Make available again">
                                                    Restock
                                                </button>
                                            @else
                                                <button type="button"
                                                        wire:click="toggleSoldOut({{ $row->menu_item_id }})"
                                                        wire:confirm="Mark {{ $row->item_name }} as sold out? New orders will be blocked."
                                                        wire:loading.attr="disabled"
                                                        class="rounded-md border border-slate-300 bg-white px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-slate-600 transition hover:bg-rose-50 hover:border-rose-300 hover:text-rose-700"
                                                        title="Mark sold out">
                                                    Sold out
                                                </button>
                                            @endif
                                        @endcan
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        {{-- ─── Status columns ─────────────────────────────── --}}
        <div class="grid gap-4 lg:grid-cols-3">

            @foreach ($columns as $col)
                @php
                    $colOrders = $grouped[$col['key']] ?? collect();
                    $style = $colStyles[$col['key']];
                @endphp

                <div wire:key="col-{{ $col['key'] }}"
                     class="flex flex-col rounded-xl border {{ $style['border'] }} bg-slate-50 shadow-sm">

                    {{-- Column header --}}
                    <div class="rounded-t-xl {{ $style['header'] }} px-4 py-3">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-sm font-bold">{{ $col['label'] }}</h2>
                                <p class="text-[10px] uppercase tracking-wider opacity-75">{{ $col['hint'] }}</p>
                            </div>
                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-white/40 text-sm font-bold">
                                {{ $colOrders->count() }}
                            </div>
                        </div>

                        {{-- Bulk actions for New Orders --}}
                        @if ($col['key'] === 'new' && $colOrders->count() > 0 && auth()->user()->can('order.update-status'))
                            <div class="mt-3 flex gap-2">
                                <button type="button"
                                        wire:click="forwardAll"
                                        wire:confirm="Forward all {{ $colOrders->count() }} new orders to the kitchen?"
                                        wire:loading.attr="disabled"
                                        class="flex-1 rounded-lg bg-white/80 px-2 py-1.5 text-xs font-semibold text-amber-900 shadow-sm hover:bg-white">
                                    <span class="inline-flex items-center gap-1">
                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                                        </svg>
                                        Forward All
                                    </span>
                                </button>
                                @can('order.cancel')
                                    <button type="button"
                                            wire:click="openBulkReject"
                                            wire:loading.attr="disabled"
                                            class="flex-1 rounded-lg bg-white/80 px-2 py-1.5 text-xs font-semibold text-rose-700 shadow-sm hover:bg-white">
                                        <span class="inline-flex items-center gap-1">
                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                            Reject All
                                        </span>
                                    </button>
                                @endcan
                            </div>
                        @endif
                    </div>

                    {{-- Cards --}}
                    <div class="flex-1 space-y-3 p-3">
                        @forelse ($colOrders as $order)
                            @php
                                // Determine the per-order action for non-new columns
                                $advanceStatus = null;
                                $advanceLabel = null;

                                if ($order->status === Order::STATUS_PREPARING) {
                                    $advanceStatus = Order::STATUS_READY;
                                    $advanceLabel = 'Mark Ready';
                                } elseif ($order->status === Order::STATUS_READY) {
                                    $advanceStatus = $order->isDelivery() ? Order::STATUS_DELIVERED : Order::STATUS_PICKED_UP;
                                    $advanceLabel = $order->isDelivery() ? 'Mark Delivered' : 'Mark Picked Up';
                                }

                                $isNew = in_array($order->status, [Order::STATUS_PENDING, Order::STATUS_CONFIRMED], true);
                            @endphp

                            <div wire:key="order-{{ $order->id }}"
                                 class="rounded-lg border border-slate-200 bg-white shadow-sm transition hover:shadow-md">

                                {{-- Header --}}
                                <div class="flex items-center justify-between border-b border-slate-100 px-3 py-2">
                                    <div class="flex items-center gap-1.5">
                                        <span class="font-mono text-xs font-semibold text-slate-600">
                                            {{ $order->order_number }}
                                        </span>
                                        @if ($order->isManual())
                                            <span class="rounded bg-amber-100 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wider text-amber-800">
                                                Manual
                                            </span>
                                        @endif
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
                                                <li wire:key="item-{{ $order->id }}-{{ $item->id }}"
                                                    class="flex items-start gap-2 text-sm text-slate-800">
                                                    <span class="{{ $item->is_veg ? 'text-emerald-600' : 'text-rose-600' }} mt-0.5">●</span>
                                                    <span class="flex-1 font-medium">
                                                        @if ($item->quantity > 1)
                                                            <span class="mr-1 inline-flex items-center rounded bg-brand-100 px-1.5 text-xs font-bold text-brand-700">{{ $item->quantity }}×</span>
                                                        @endif
                                                        {{ $item->item_name }}
                                                    </span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>

                                    <div class="flex items-center justify-between border-t border-slate-100 pt-2 text-[11px] text-slate-500">
                                        <span>Placed {{ $order->created_at->format('H:i') }}</span>
                                        <span class="font-semibold text-slate-700">Nu. {{ number_format($order->total, 0) }}</span>
                                    </div>
                                </div>

                                {{-- Action buttons --}}
                                @if ($isNew)
                                    {{-- Forward or Reject --}}
                                    @if (auth()->user()->can('order.update-status'))
                                        <div class="flex border-t border-slate-100">
                                            @can('order.cancel')
                                                <button type="button"
                                                        wire:click="openReject({{ $order->id }})"
                                                        wire:loading.attr="disabled"
                                                        class="flex-1 rounded-bl-lg border-r border-slate-100 py-3 text-xs font-bold text-rose-700 transition hover:bg-rose-50">
                                                    <span class="inline-flex items-center justify-center gap-1">
                                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                                                        </svg>
                                                        Reject
                                                    </span>
                                                </button>
                                            @endcan
                                            <button type="button"
                                                    wire:click="forward({{ $order->id }})"
                                                    wire:loading.attr="disabled"
                                                    class="flex-1 rounded-br-lg py-3 text-xs font-bold text-white transition {{ $style['btn'] }}">
                                                <span class="inline-flex items-center justify-center gap-1">
                                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                                                    </svg>
                                                    Forward to Kitchen
                                                </span>
                                            </button>
                                        </div>
                                    @endif
                                @elseif ($advanceStatus)
                                    @if (auth()->user()->can('order.update-status'))
                                        <button type="button"
                                                wire:click="advance({{ $order->id }}, '{{ $advanceStatus }}')"
                                                wire:loading.attr="disabled"
                                                class="w-full rounded-b-lg py-3 text-xs font-bold text-white transition {{ $style['btn'] }}">
                                            {{ $advanceLabel }} →
                                        </button>
                                    @endif
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

    {{-- ─── Reject modal ───────────────────────────────────── --}}
    <x-admin.modal name="reject-order" :title="$rejectingId ? 'Reject Order' : 'Reject All New Orders'" maxWidth="md">
        @if ($showRejectModal)
            <form wire:submit="confirmReject" class="space-y-4">

                <x-admin.alert type="warning">
                    @if ($rejectingId)
                        Rejecting this order will <strong>refund the customer's wallet</strong> automatically. The reason below will appear in their order history.
                    @else
                        Rejecting <strong>all new orders</strong> will refund each customer's wallet automatically. The same reason will appear in all their order histories.
                    @endif
                </x-admin.alert>

                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">
                        Reason (visible to customer) <span class="text-rose-500">*</span>
                    </label>
                    <textarea wire:model="rejectReason" rows="3"
                              placeholder="e.g. Kitchen closed early — delivery van broke down"
                              class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"></textarea>
                    @error('rejectReason') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" wire:click="closeReject"
                            class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                        Cancel
                    </button>
                    <button type="submit"
                            wire:loading.attr="disabled" wire:target="confirmReject"
                            class="inline-flex items-center gap-2 rounded-lg bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-700 disabled:opacity-50">
                        <span wire:loading.remove wire:target="confirmReject">
                            {{ $rejectingId ? 'Reject & refund' : 'Reject all & refund' }}
                        </span>
                        <span wire:loading wire:target="confirmReject">Rejecting…</span>
                    </button>
                </div>
            </form>
        @endif
    </x-admin.modal>
</div>