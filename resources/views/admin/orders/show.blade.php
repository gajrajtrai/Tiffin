<div class="mx-auto max-w-4xl space-y-6">

    {{-- ─── Back link ──────────────────────────────────────── --}}
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.orders.index') }}"
           class="inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-brand-600">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Orders
        </a>

        <div class="font-mono text-xs text-slate-500">{{ $order->order_number }}</div>
    </div>

    @if ($statusMessage)
        <x-admin.alert :type="$statusType">{{ $statusMessage }}</x-admin.alert>
    @endif

    {{-- ─── Header card ─────────────────────────────────────── --}}
    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="font-mono text-xl font-bold text-slate-900">{{ $order->order_number }}</h1>
                    <x-admin.badge :variant="$order->statusVariant()">
                        {{ $order->statusLabel() }}
                    </x-admin.badge>
                    @if ($order->isDelivery())
                        <x-admin.badge variant="info">Delivery</x-admin.badge>
                    @else
                        <x-admin.badge variant="slate">Pickup</x-admin.badge>
                    @endif
                    <x-admin.badge variant="success">Paid from wallet</x-admin.badge>
                </div>
                <div class="mt-2 text-sm text-slate-600">
                    Service date: <strong>{{ $order->service_date->format('l, F j, Y') }}</strong>
                    @if ($order->delivery_slot)
                        · Slot: <strong>{{ $order->delivery_slot }}</strong>
                    @endif
                </div>
                <div class="mt-1 text-xs text-slate-400">
                    Placed {{ $order->created_at->format('M j, Y \a\t H:i') }}
                </div>
            </div>

            {{-- Action buttons --}}
            <div class="flex flex-wrap items-start gap-2">
                @can('order.update-status')
                    @foreach ($nextActions as $action)
                        <button type="button"
                                wire:click="advance('{{ $action['status'] }}')"
                                wire:loading.attr="disabled"
                                class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold text-white shadow-sm
                                       {{ $action['variant'] === 'success' ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-brand-500 hover:bg-brand-600' }}">
                            {{ $action['label'] }}
                        </button>
                    @endforeach
                @endcan

                @if ($order->isCancellable() && auth()->user()->can('order.cancel'))
                    <button type="button"
                            x-data
                            @click="$dispatch('open-modal-cancel-order')"
                            class="rounded-lg border border-rose-300 bg-white px-4 py-2 text-sm font-medium text-rose-700 hover:bg-rose-50">
                        Cancel order
                    </button>
                @endif
            </div>
        </div>

        @if ($order->isCancelled())
            <div class="mt-4 rounded-lg border border-rose-200 bg-rose-50 p-3">
                <div class="text-xs font-semibold text-rose-800">Cancelled {{ $order->cancelled_at?->format('M j, Y \a\t H:i') }}</div>
                @if ($order->cancelled_reason)
                    <div class="mt-1 text-sm text-rose-700">{{ $order->cancelled_reason }}</div>
                @endif
            </div>
        @endif
    </div>

    {{-- ─── Customer + payment ─────────────────────────────── --}}
    <div class="grid gap-4 sm:grid-cols-2">

        {{-- Customer --}}
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <h3 class="mb-3 text-sm font-bold text-slate-900">Customer</h3>
            <div class="flex items-start gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-500 text-xs font-bold text-white">
                    {{ $order->user->initials() }}
                </div>
                <div class="min-w-0 flex-1">
                    <div class="font-medium text-slate-900">{{ $order->user->name }}</div>
                    @if ($order->user->mobile)
                        <div class="text-sm text-slate-600">{{ $order->user->mobile }}</div>
                    @endif
                    <div class="mt-1 text-xs text-slate-400">
                        Wallet balance: <strong class="text-slate-600">Nu. {{ number_format($order->user->wallet_balance, 2) }}</strong>
                    </div>
                </div>
            </div>
            <a href="{{ route('admin.users.show', $order->user) }}"
               class="mt-3 inline-flex items-center gap-1 text-xs font-medium text-brand-600 hover:text-brand-700">
                View customer profile
                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>

        {{-- Payment --}}
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <h3 class="mb-3 text-sm font-bold text-slate-900">Payment</h3>
            <div class="flex items-baseline justify-between">
                <span class="text-xs text-slate-500">Amount charged</span>
                <span class="text-lg font-bold text-slate-900">Nu. {{ number_format($order->total, 2) }}</span>
            </div>
            @if ($order->walletTransaction)
                <div class="mt-3 space-y-1 text-xs text-slate-500">
                    <div class="flex justify-between">
                        <span>Debit from wallet</span>
                        <span>Nu. {{ number_format($order->walletTransaction->amount, 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Balance after</span>
                        <span>Nu. {{ number_format($order->walletTransaction->balance_after, 2) }}</span>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- ─── Items ───────────────────────────────────────────── --}}
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-4 py-3">
            <h3 class="text-sm font-bold text-slate-900">Order Items</h3>
        </div>
        <div class="divide-y divide-slate-100">
            @foreach ($order->items as $item)
                <div class="flex items-center gap-3 px-4 py-3">
                    <span class="text-base {{ $item->is_veg ? 'text-emerald-600' : 'text-rose-600' }}">●</span>
                    <div class="min-w-0 flex-1">
                        <div class="font-medium text-slate-900">
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
                    <div class="shrink-0 font-semibold text-slate-900">
                        Nu. {{ number_format($item->lineTotal(), 2) }}
                    </div>
                </div>
            @endforeach
        </div>
        <div class="flex items-center justify-between border-t border-slate-200 bg-slate-50 px-4 py-3">
            <span class="text-sm font-semibold text-slate-700">Total</span>
            <span class="text-lg font-bold text-slate-900">Nu. {{ number_format($order->total, 2) }}</span>
        </div>
    </div>

    {{-- ─── Cancel modal ────────────────────────────────────── --}}
    <x-admin.modal name="cancel-order" title="Cancel Order">
        <form wire:submit="cancel" class="space-y-4">
            <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800">
                <strong>This will:</strong>
                <ul class="mt-1 ml-4 list-disc space-y-0.5">
                    <li>Set the order status to <strong>Cancelled</strong></li>
                    <li>Refund <strong>Nu. {{ number_format($order->total, 2) }}</strong> to the customer's wallet</li>
                    <li>Record a refund transaction in the ledger</li>
                </ul>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-600">Reason for cancellation <span class="text-rose-500">*</span></label>
                <textarea wire:model="cancelReason" rows="3"
                          placeholder="e.g. Kitchen closed early / customer requested"
                          class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"></textarea>
                @error('cancelReason') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button"
                        x-data @click="$dispatch('close-modal-cancel-order')"
                        class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    Keep order
                </button>
                <button type="submit"
                        class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-700">
                    Cancel &amp; refund
                </button>
            </div>
        </form>
    </x-admin.modal>
</div>