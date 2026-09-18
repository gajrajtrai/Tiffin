<div class="space-y-6">

    {{-- ─── Back link + flash ──────────────────────────────── --}}
    <div>
        <a href="{{ route('admin.users.index') }}"
           class="inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-brand-600">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Users
        </a>
    </div>

    @if ($statusMessage)
        <x-admin.alert :type="$statusType">{{ $statusMessage }}</x-admin.alert>
    @endif

    {{-- ─── Header card ─────────────────────────────────────── --}}
    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">

            {{-- Avatar + identity --}}
            <div class="flex items-start gap-4">
                <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-brand-500 text-xl font-bold text-white">
                    {{ $user->initials() }}
                </div>
                <div>
                    <h2 class="text-xl font-bold text-slate-900">{{ $user->name }}</h2>
                    <div class="mt-1 flex flex-wrap items-center gap-2">
                        @foreach ($user->roles as $role)
                            <x-admin.badge
                                :variant="match($role->name) {
                                    'Admin' => 'danger',
                                    'Manager' => 'brand',
                                    'Kitchen Staff' => 'info',
                                    'Delivery Staff' => 'warning',
                                    'Customer' => 'slate',
                                    default => 'slate',
                                }">
                                {{ $role->name }}
                            </x-admin.badge>
                        @endforeach
                        <x-admin.badge :variant="$user->status === 'active' ? 'success' : 'danger'">
                            {{ ucfirst($user->status) }}
                        </x-admin.badge>
                    </div>
                    <div class="mt-2 space-y-0.5 text-sm text-slate-600">
                        @if ($user->mobile)
                            <div>{{ $user->mobile }}</div>
                        @endif
                        @if ($user->email)
                            <div class="text-slate-500">{{ $user->email }}</div>
                        @endif
                        <div class="text-xs text-slate-400">
                            Joined {{ $user->created_at->format('M j, Y') }} · User #{{ $user->id }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Action buttons --}}
            <div class="flex flex-wrap items-start gap-2">
                @if (auth()->user()->can('user.edit') && ! ($user->hasRole('Customer') && $user->roles->count() === 1))
                    <a href="{{ route('admin.users.edit', $user) }}"
                       class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Edit user
                    </a>
                @endif

                @if (auth()->user()->can('user.edit') && $user->id !== auth()->id())
                    <form method="POST" wire:submit="toggleStatus">
                        <button type="submit"
                                class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium
                                       {{ $user->status === 'active' ? 'text-rose-600 hover:bg-rose-50 hover:border-rose-300' : 'text-emerald-600 hover:bg-emerald-50 hover:border-emerald-300' }}">
                            {{ $user->status === 'active' ? 'Suspend account' : 'Reactivate account' }}
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    {{-- ─── Summary strip ───────────────────────────────────── --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-medium uppercase tracking-wider text-slate-500">Wallet Balance</div>
            <div class="mt-1 text-2xl font-bold text-slate-900">
                Nu. {{ number_format($user->wallet_balance, 2) }}
            </div>
            @if ($user->hasLowBalance())
                <div class="mt-1 text-xs font-medium text-rose-600">Below threshold (Nu. {{ number_format($user->low_balance_threshold, 0) }})</div>
            @endif
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-medium uppercase tracking-wider text-slate-500">Total Orders</div>
            <div class="mt-1 text-2xl font-bold text-slate-900">{{ $stats['orderCount'] }}</div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-medium uppercase tracking-wider text-slate-500">Lifetime Value</div>
            <div class="mt-1 text-2xl font-bold text-slate-900">
                Nu. {{ number_format($stats['lifetimeValue'], 0) }}
            </div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-medium uppercase tracking-wider text-slate-500">Pending Proofs</div>
            <div class="mt-1 text-2xl font-bold text-slate-900">{{ $stats['pendingProofs'] }}</div>
        </div>
    </div>

    {{-- ─── Wallet actions ──────────────────────────────────── --}}
    @if (auth()->user()->can('wallet.credit') || auth()->user()->can('wallet.debit'))
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex flex-wrap items-center gap-3">
                <div class="text-sm font-semibold text-slate-700">Wallet actions:</div>

                @if (auth()->user()->can('wallet.credit'))
                    <button type="button"
                            x-data
                            @click="$dispatch('open-modal-credit-wallet')"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-emerald-700">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Credit wallet
                    </button>
                @endif

                @if (auth()->user()->can('wallet.debit'))
                    <button type="button"
                            x-data
                            @click="$dispatch('open-modal-debit-wallet')"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-rose-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-rose-700">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                        </svg>
                        Debit wallet
                    </button>
                @endif
            </div>
        </div>
    @endif

    {{-- ─── Main grid ───────────────────────────────────────── --}}
    <div class="grid gap-6 lg:grid-cols-3">

        {{-- Orders + Ledger --}}
        <div class="lg:col-span-2 space-y-6">
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
                    <h3 class="text-sm font-bold text-slate-900">Order History</h3>
                    <span class="text-xs text-slate-500">{{ $stats['orderCount'] }} orders</span>
                </div>

                @if ($orders->isEmpty())
                    <div class="p-8 text-center">
                        <p class="text-sm text-slate-500">No orders yet.</p>
                    </div>
                @else
                    <div class="divide-y divide-slate-100">
                        @foreach ($orders as $order)
                            <div class="flex items-center justify-between px-4 py-3">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono text-xs text-slate-500">{{ $order->order_number }}</span>
                                        <x-admin.badge :variant="$order->statusVariant()">{{ $order->statusLabel() }}</x-admin.badge>
                                    </div>
                                    <div class="mt-1 text-xs text-slate-500">
                                        {{ $order->service_date->format('M j, Y') }} ·
                                        {{ $order->isDelivery() ? 'Delivery' : 'Pickup' }}
                                    </div>
                                    <div class="mt-1 text-xs text-slate-600">
                                        @foreach ($order->items as $item)
                                            <span class="{{ $item->is_veg ? 'text-emerald-600' : 'text-rose-600' }}">●</span>
                                            {{ $item->item_name }}@if(!$loop->last)<span class="text-slate-400">, </span>@endif
                                        @endforeach
                                    </div>
                                </div>
                                <div class="ml-3 shrink-0 text-right">
                                    <div class="font-semibold text-slate-900">Nu. {{ number_format($order->total, 2) }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if ($orders->hasPages())
                        <div class="border-t border-slate-100 px-4 py-3">
                            {{ $orders->links() }}
                        </div>
                    @endif
                @endif
            </div>

            {{-- Wallet ledger --}}
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
                    <h3 class="text-sm font-bold text-slate-900">Wallet Ledger</h3>
                    <span class="text-xs text-slate-500">Last 20 transactions</span>
                </div>

                @if ($transactions->isEmpty())
                    <div class="p-8 text-center">
                        <p class="text-sm text-slate-500">No wallet activity.</p>
                    </div>
                @else
                    <div class="divide-y divide-slate-100">
                        @foreach ($transactions as $txn)
                            <div class="flex items-center justify-between px-4 py-3">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <x-admin.badge :variant="$txn->isCredit() ? 'success' : 'danger'">
                                            {{ $txn->isCredit() ? 'Credit' : 'Debit' }}
                                        </x-admin.badge>
                                        <span class="text-xs text-slate-400">{{ $txn->created_at->format('M j, H:i') }}</span>
                                    </div>
                                    <div class="mt-1 truncate text-sm text-slate-700">{{ $txn->description }}</div>
                                </div>
                                <div class="ml-3 shrink-0 text-right">
                                    <div class="font-semibold {{ $txn->isCredit() ? 'text-emerald-600' : 'text-rose-600' }}">
                                        {{ $txn->signed_amount }}
                                    </div>
                                    <div class="text-xs text-slate-400">bal {{ number_format($txn->balance_after, 2) }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Payment proofs sidebar --}}
        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-4 py-3">
                <h3 class="text-sm font-bold text-slate-900">Payment Proofs</h3>
            </div>

            @if ($paymentProofs->isEmpty())
                <div class="p-8 text-center">
                    <p class="text-xs text-slate-500">No payment proofs uploaded.</p>
                </div>
            @else
                <div class="divide-y divide-slate-100">
                    @foreach ($paymentProofs as $proof)
                        <div class="px-4 py-3">
                            <div class="flex items-center justify-between">
                                <x-admin.badge :variant="$proof->statusVariant()">{{ $proof->statusLabel() }}</x-admin.badge>
                                <span class="text-xs text-slate-400">{{ $proof->created_at->format('M j') }}</span>
                            </div>
                            <div class="mt-2 text-sm font-semibold text-slate-900">
                                Nu. {{ number_format($proof->claimed_amount, 2) }}
                            </div>
                            @if ($proof->bank_reference)
                                <div class="text-xs text-slate-500">Ref: {{ $proof->bank_reference }}</div>
                            @endif
                            @if ($proof->note)
                                <div class="mt-1 text-xs text-slate-600 italic">"{{ $proof->note }}"</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- ─── Credit modal ────────────────────────────────────── --}}
    <x-admin.modal name="credit-wallet" title="Credit Wallet">
        <form wire:submit="creditWallet" class="space-y-4">
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-600">Amount (Nu.)</label>
                <input type="number" step="0.01" min="1" max="50000"
                       wire:model="creditAmount"
                       placeholder="500"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                @error('creditAmount') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-600">Reason (required for audit)</label>
                <input type="text" wire:model="creditReason"
                       placeholder="e.g. Cash payment at counter"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                @error('creditReason') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button"
                        x-data @click="$dispatch('close-modal-credit-wallet')"
                        class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    Cancel
                </button>
                <button type="submit"
                        class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                    Credit wallet
                </button>
            </div>
        </form>
    </x-admin.modal>

    {{-- ─── Debit modal ─────────────────────────────────────── --}}
    <x-admin.modal name="debit-wallet" title="Debit Wallet">
        <form wire:submit="debitWallet" class="space-y-4">
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-600">Amount (Nu.)</label>
                <input type="number" step="0.01" min="1" max="50000"
                       wire:model="debitAmount"
                       placeholder="200"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                @error('debitAmount') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-600">Reason (required for audit)</label>
                <input type="text" wire:model="debitReason"
                       placeholder="e.g. Refund / correction"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                @error('debitReason') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button"
                        x-data @click="$dispatch('close-modal-debit-wallet')"
                        class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    Cancel
                </button>
                <button type="submit"
                        class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-700">
                    Debit wallet
                </button>
            </div>
        </form>
    </x-admin.modal>
</div>