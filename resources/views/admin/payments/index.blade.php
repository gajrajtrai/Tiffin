<div class="space-y-6">

    {{-- ─── Page header ────────────────────────────────────── --}}
    <div>
        <h1 class="text-xl font-bold text-slate-900">Payment Verification</h1>
        <p class="mt-1 text-sm text-slate-500">
            Review payment screenshots from customers and credit wallets on approval.
        </p>
    </div>

    @if ($statusMessage)
        <x-admin.alert :type="$statusType">{{ $statusMessage }}</x-admin.alert>
    @endif

    {{-- ─── Stats ──────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <x-admin.stat-card label="Pending" :value="$counts['pending']" icon="credit-card" color="brand" />
        <x-admin.stat-card label="Pending Amount" :value="'Nu. '.number_format($counts['pendingAmount'], 0)" icon="banknotes" color="rose" />
        <x-admin.stat-card label="Approved" :value="$counts['approved']" icon="credit-card" color="emerald" />
        <x-admin.stat-card label="Rejected" :value="$counts['rejected']" icon="credit-card" color="sky" />
    </div>

    {{-- ─── Tabs + search ──────────────────────────────────── --}}
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">

            <div class="inline-flex rounded-lg border border-slate-200 p-0.5">
                @php
                    $tabs = [
                        ['key' => 'pending',  'label' => 'Pending',  'count' => $counts['pending']],
                        ['key' => 'approved', 'label' => 'Approved', 'count' => $counts['approved']],
                        ['key' => 'rejected', 'label' => 'Rejected', 'count' => $counts['rejected']],
                        ['key' => 'all',      'label' => 'All',      'count' => $counts['pending'] + $counts['approved'] + $counts['rejected']],
                    ];
                @endphp

                @foreach ($tabs as $tab)
                    <button type="button"
                            wire:click="setStatus('{{ $tab['key'] }}')"
                            @class([
                                'rounded-md px-3 py-1.5 text-sm font-medium transition',
                                'bg-brand-500 text-white shadow-sm' => $statusFilter === $tab['key'],
                                'text-slate-600 hover:text-slate-900' => $statusFilter !== $tab['key'],
                            ])>
                        {{ $tab['label'] }}
                        <span class="ml-1 text-xs opacity-75">{{ $tab['count'] }}</span>
                    </button>
                @endforeach
            </div>

            <div class="relative">
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/>
                </svg>
                <input type="text" wire:model.live.debounce.300ms="search"
                       placeholder="Search by name, mobile, or reference"
                       class="w-full rounded-lg border border-slate-300 py-2 pl-9 pr-3 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 lg:w-80" />
            </div>
        </div>
    </div>

    {{-- ─── Proofs list ────────────────────────────────────── --}}
    @if ($proofs->isEmpty())
        <div class="rounded-xl border border-slate-200 bg-white p-12 text-center">
            <p class="text-sm text-slate-500">No payment proofs in this view.</p>
        </div>
    @else
        <x-admin.data-table :headers="['Proof', 'Customer', 'Claimed', 'Reference', 'Submitted', 'Status', '']">
            @foreach ($proofs as $proof)
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3">
                        <div class="font-mono text-xs text-slate-500">#{{ $proof->id }}</div>
                        @if ($proof->hasMedia('screenshot'))
                            <div class="mt-1 inline-flex items-center gap-1 text-[10px] font-medium uppercase text-emerald-600">
                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                Screenshot
                            </div>
                        @else
                            <div class="mt-1 text-[10px] uppercase text-slate-400">No image</div>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="font-medium text-slate-900">{{ $proof->user->name }}</div>
                        <div class="text-xs text-slate-500">{{ $proof->user->mobile }}</div>
                    </td>
                    <td class="px-4 py-3 font-semibold text-slate-900">
                        Nu. {{ number_format($proof->claimed_amount, 2) }}
                    </td>
                    <td class="px-4 py-3">
                        @if ($proof->bank_reference)
                            <div class="font-mono text-xs text-slate-600">{{ $proof->bank_reference }}</div>
                        @else
                            <span class="text-xs text-slate-400">—</span>
                        @endif
                        @if ($proof->note)
                            <div class="mt-1 max-w-xs truncate text-xs italic text-slate-500">"{{ $proof->note }}"</div>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="text-xs text-slate-600">{{ $proof->created_at->format('M j, H:i') }}</div>
                        @if ($proof->reviewed_at)
                            <div class="text-[10px] text-slate-400">Reviewed {{ $proof->reviewed_at->diffForHumans() }}</div>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <x-admin.badge :variant="$proof->statusVariant()">
                            {{ $proof->statusLabel() }}
                        </x-admin.badge>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <button type="button"
                                wire:click="openReview({{ $proof->id }})"
                                wire:loading.attr="disabled"
                                wire:target="openReview"
                                class="inline-flex items-center gap-1 text-sm font-medium text-brand-600 hover:text-brand-700 disabled:opacity-50">
                            {{ $proof->isPending() ? 'Review' : 'View' }}
                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </td>
                </tr>
            @endforeach
        </x-admin.data-table>

        <div>{{ $proofs->links() }}</div>
    @endif

    {{-- ─── Review modal ───────────────────────────────────── --}}
    <x-admin.modal name="review-payment" title="Review Payment Proof" maxWidth="xl">

        @if ($reviewing)

            {{-- Meta grid --}}
            <div class="grid grid-cols-2 gap-3 text-sm">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Customer</div>
                    <div class="mt-0.5 font-medium text-slate-900">{{ $reviewing->user->name }}</div>
                    <div class="text-xs text-slate-500">{{ $reviewing->user->mobile }}</div>
                </div>
                <div>
                    <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Claimed Amount</div>
                    <div class="mt-0.5 text-lg font-bold text-slate-900">
                        Nu. {{ number_format($reviewing->claimed_amount, 2) }}
                    </div>
                </div>
                <div>
                    <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Bank Reference</div>
                    <div class="mt-0.5 font-mono text-slate-700">{{ $reviewing->bank_reference ?? '—' }}</div>
                </div>
                <div>
                    <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Submitted</div>
                    <div class="mt-0.5 text-slate-700">{{ $reviewing->created_at->format('M j, Y \a\t H:i') }}</div>
                </div>
            </div>

            @if ($reviewing->note)
                <div class="mt-4 rounded-lg border border-slate-200 bg-slate-50 p-3">
                    <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Customer Note</div>
                    <div class="mt-1 text-sm italic text-slate-700">"{{ $reviewing->note }}"</div>
                </div>
            @endif

            {{-- Screenshot --}}
            <div class="mt-4">
                <div class="mb-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Payment Screenshot</div>
                @if ($reviewing->hasMedia('screenshot'))
                    <a href="{{ $reviewing->getFirstMediaUrl('screenshot') }}" target="_blank"
                       class="block overflow-hidden rounded-lg border border-slate-200 hover:border-brand-400">
                        <img src="{{ $reviewing->getFirstMediaUrl('screenshot') }}"
                             alt="Payment screenshot"
                             class="max-h-96 w-full bg-slate-50 object-contain" />
                    </a>
                    <p class="mt-1 text-[11px] text-slate-400">Click to open in a new tab</p>
                @else
                    <div class="flex h-32 items-center justify-center rounded-lg border border-dashed border-slate-300 bg-slate-50">
                        <p class="text-xs text-slate-500">No screenshot attached — verify via bank statement.</p>
                    </div>
                @endif
            </div>

            {{-- Action forms --}}
            @if ($reviewing->isPending())
                <div class="mt-5 grid gap-3 sm:grid-cols-2">

                    <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-3">
                        <h3 class="text-sm font-bold text-emerald-900">Approve</h3>
                        <p class="mt-0.5 text-xs text-emerald-700">Credits the wallet now.</p>

                        <div class="mt-3">
                            <label class="mb-1 block text-xs font-medium text-emerald-800">Amount to credit (Nu.)</label>
                            <input type="number" step="0.01" min="1" max="50000"
                                   wire:model="approvalAmount"
                                   class="w-full rounded-lg border border-emerald-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500" />
                            @error('approvalAmount') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                            <p class="mt-1 text-[11px] text-emerald-700">
                                Customer claimed Nu. {{ number_format($reviewing->claimed_amount, 2) }}.
                            </p>
                        </div>

                        <button type="button" wire:click="approve"
                                wire:loading.attr="disabled" wire:target="approve"
                                class="mt-3 w-full rounded-lg bg-emerald-600 py-2 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-50">
                            <span wire:loading.remove wire:target="approve">Approve &amp; credit</span>
                            <span wire:loading wire:target="approve">Approving…</span>
                        </button>
                    </div>

                    <div class="rounded-lg border border-rose-200 bg-rose-50 p-3">
                        <h3 class="text-sm font-bold text-rose-900">Reject</h3>
                        <p class="mt-0.5 text-xs text-rose-700">No wallet change.</p>

                        <div class="mt-3">
                            <label class="mb-1 block text-xs font-medium text-rose-800">Reason</label>
                            <textarea wire:model="rejectionReason" rows="3"
                                      placeholder="e.g. Screenshot does not match reference"
                                      class="w-full rounded-lg border border-rose-300 px-3 py-2 text-sm focus:border-rose-500 focus:outline-none focus:ring-1 focus:ring-rose-500"></textarea>
                            @error('rejectionReason') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>

                        <button type="button" wire:click="reject"
                                wire:loading.attr="disabled" wire:target="reject"
                                class="mt-3 w-full rounded-lg bg-rose-600 py-2 text-sm font-semibold text-white hover:bg-rose-700 disabled:opacity-50">
                            <span wire:loading.remove wire:target="reject">Reject proof</span>
                            <span wire:loading wire:target="reject">Rejecting…</span>
                        </button>
                    </div>
                </div>
            @endif

            {{-- Already reviewed --}}
            @if (! $reviewing->isPending())
                <div class="mt-4 rounded-lg border {{ $reviewing->isApproved() ? 'border-emerald-200 bg-emerald-50' : 'border-rose-200 bg-rose-50' }} p-3">
                    <div class="text-xs font-semibold uppercase tracking-wider {{ $reviewing->isApproved() ? 'text-emerald-800' : 'text-rose-800' }}">
                        {{ $reviewing->statusLabel() }} {{ $reviewing->reviewed_at?->diffForHumans() }}
                    </div>
                    @if ($reviewing->isRejected() && $reviewing->rejection_reason)
                        <div class="mt-1 text-sm text-rose-700">{{ $reviewing->rejection_reason }}</div>
                    @endif
                    @if ($reviewing->walletTransaction)
                        <div class="mt-2 text-xs text-emerald-700">
                            Credited: <strong>Nu. {{ number_format($reviewing->walletTransaction->amount, 2) }}</strong>
                            · New balance: Nu. {{ number_format($reviewing->walletTransaction->balance_after, 2) }}
                        </div>
                    @endif
                </div>
            @endif

        @else
            <p class="text-sm text-slate-500">Loading…</p>
        @endif

        <x-slot:footer>
            <button type="button"
                    x-data @click="$dispatch('close-modal-review-payment')"
                    class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                Close
            </button>
        </x-slot:footer>
    </x-admin.modal>
</div>