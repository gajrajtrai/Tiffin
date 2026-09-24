<div class="space-y-6">

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Account Closure Requests</h1>
            <p class="mt-1 text-sm text-slate-500">
                Approving a request suspends the customer's login. Data is preserved.
            </p>
        </div>
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

    {{-- Stats --}}
    <div class="grid grid-cols-3 gap-4">
        <x-admin.stat-card label="Pending" :value="$counts['pending']" icon="users" color="rose" />
        <x-admin.stat-card label="Approved" :value="$counts['approved']" icon="users" color="slate" />
        <x-admin.stat-card label="Rejected" :value="$counts['rejected']" icon="users" color="brand" />
    </div>

    {{-- Tabs --}}
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="inline-flex rounded-lg border border-slate-200 p-0.5">
            @foreach ([
                ['key' => 'pending',  'label' => 'Pending',  'count' => $counts['pending']],
                ['key' => 'approved', 'label' => 'Approved', 'count' => $counts['approved']],
                ['key' => 'rejected', 'label' => 'Rejected', 'count' => $counts['rejected']],
                ['key' => 'all',      'label' => 'All',      'count' => array_sum($counts)],
            ] as $tab)
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
    </div>

    {{-- List --}}
    @if ($requests->isEmpty())
        <div class="rounded-xl border border-slate-200 bg-white p-12 text-center">
            <p class="text-sm text-slate-500">No closure requests in this view.</p>
        </div>
    @else
        <div class="space-y-3">
            @foreach ($requests as $req)
                <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex flex-col gap-3 p-4 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-medium text-slate-900">{{ $req->user?->name ?? 'Deleted user' }}</span>
                                <x-admin.badge :variant="$req->statusVariant()">{{ $req->statusLabel() }}</x-admin.badge>
                            </div>
                            <div class="mt-1 text-xs text-slate-500">
                                {{ $req->user?->mobile }}
                                · Requested {{ $req->created_at->format('M j, Y · H:i') }}
                            </div>
                            @if ($req->reason)
                                <div class="mt-2 max-w-xl text-sm italic text-slate-600">
                                    "{{ $req->reason }}"
                                </div>
                            @endif
                            @if ($req->reviewed_at)
                                <div class="mt-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600">
                                    <strong>Reviewed</strong> {{ $req->reviewed_at->format('M j, Y') }}
                                    by {{ $req->reviewedBy?->name ?? 'system' }}
                                    @if ($req->admin_notes)
                                        · <em>{{ $req->admin_notes }}</em>
                                    @endif
                                </div>
                            @endif
                        </div>

                        @if ($req->isPending())
                            <div class="shrink-0">
                                <button type="button" wire:click="openReview({{ $req->id }})"
                                        class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-600">
                                    Review
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <div>{{ $requests->links() }}</div>
    @endif

    {{-- Review modal --}}
    <x-admin.modal name="review-closure" title="Review Closure Request" maxWidth="md">
        @if ($reviewing)
            <div class="space-y-4">
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm">
                    <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Customer</div>
                    <div class="mt-0.5 font-medium text-slate-900">{{ $reviewing->user?->name }}</div>
                    <div class="text-xs text-slate-500">
                        {{ $reviewing->user?->mobile }}
                        · Wallet: Nu. {{ number_format((float) ($reviewing->user?->wallet_balance ?? 0), 2) }}
                    </div>
                </div>

                @if ($reviewing->reason)
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm">
                        <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Reason</div>
                        <div class="mt-1 italic text-slate-700">"{{ $reviewing->reason }}"</div>
                    </div>
                @endif

                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">
                        Admin note (visible to customer if you reject)
                    </label>
                    <textarea wire:model="adminNotes" rows="3"
                              placeholder="Optional for approval; required for rejection"
                              class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"></textarea>
                    @error('adminNotes') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <x-admin.alert type="warning">
                    Approving will suspend the account (login blocked). The customer's
                    wallet balance and history are preserved. Handle any refunds manually.
                </x-admin.alert>
            </div>

            <x-slot:footer>
                <button type="button" wire:click="closeReview"
                        class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    Cancel
                </button>
                <button type="button" wire:click="reject"
                        wire:loading.attr="disabled" wire:target="reject"
                        class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-50">
                    Reject
                </button>
                <button type="button" wire:click="approve"
                        wire:confirm="Suspend this customer's account?"
                        wire:loading.attr="disabled" wire:target="approve"
                        class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-700 disabled:opacity-50">
                    Approve &amp; suspend
                </button>
            </x-slot:footer>
        @endif
    </x-admin.modal>
</div>