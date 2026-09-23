<div class="mx-auto max-w-3xl space-y-5 px-4 py-6 sm:py-10">

    {{-- ─── Balance hero ───────────────────────────────────── --}}
    <div class="rounded-2xl bg-gradient-to-br from-brand-500 to-brand-600 p-6 text-white shadow-lg">
        <div class="flex items-start justify-between">
            <div>
                <div class="text-xs font-medium uppercase tracking-wider text-white/80">Wallet Balance</div>
                <div class="mt-1 text-4xl font-bold">
                    Nu. {{ number_format($user->wallet_balance, 2) }}
                </div>
                @if ($user->hasLowBalance())
                    <div class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-white/20 px-3 py-1 text-xs font-medium">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01"/>
                        </svg>
                        Low — top up to keep ordering
                    </div>
                @endif
            </div>
            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-white/20">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
            </div>
        </div>
        <div class="mt-4 text-xs text-white/75">
            Hi {{ $user->name }} · {{ $user->mobile }}
        </div>
    </div>

    {{-- ─── Flash ──────────────────────────────────────────── --}}
    @if ($statusMessage)
        <x-admin.alert :type="$statusType">{{ $statusMessage }}</x-admin.alert>
    @endif

    {{-- ─── How to top up — multi-bank tabs ───────────────── --}}
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-sm font-bold text-slate-900">How to Top Up</h2>
        <p class="mt-1 text-xs text-slate-500">
            Scan our merchant QR with your bank app, transfer the amount, then upload the receipt below.
        </p>

        @if (count($settings['banks']) === 0)
            <div class="mt-4 rounded-lg border border-dashed border-slate-300 bg-slate-50 p-8 text-center">
                <svg class="mx-auto h-10 w-10 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <p class="mt-2 text-sm text-slate-600">Payment details are being set up.</p>
                <p class="mt-1 text-xs text-slate-400">Please check back shortly or contact us directly.</p>
            </div>
        @else

            {{-- Alpine tab controller --}}
            <div x-data="{ activeBank: 0 }" class="mt-4">

                {{-- Tabs --}}
                <div class="flex gap-1 overflow-x-auto border-b border-slate-200 pb-px">
                    <template x-for="(bank, i) in {{ count($settings['banks']) }}" :key="i">
                        <button type="button"
                                @click="activeBank = i"
                                :class="activeBank === i
                                    ? 'border-brand-500 text-brand-600'
                                    : 'border-transparent text-slate-500 hover:text-slate-700'"
                                class="whitespace-nowrap border-b-2 px-3 py-2 text-sm font-medium transition"
                                x-text="'{{ implode('|', array_map(fn ($b) => $b['short_name'] ?? $b['bank_name'], $settings['banks'])) }}'.split('|')[i]">
                        </button>
                    </template>
                </div>

                {{-- Panels --}}
                <div class="mt-4">
                    @foreach ($settings['banks'] as $i => $bank)
                        <div x-show="activeBank === {{ $i }}"
                             x-transition.opacity
                             class="grid gap-4 sm:grid-cols-2">

                            {{-- QR --}}
                            <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-center">
                                <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Scan with your {{ $bank['short_name'] ?? $bank['bank_name'] }} app
                                </div>
                                <div class="mt-3 flex justify-center">
                                    @if (! empty($bank['qr_image']) && file_exists(public_path('qr/'.$bank['qr_image'])))
                                        <img src="{{ asset('qr/'.$bank['qr_image']) }}"
                                             alt="{{ $bank['bank_name'] }} QR"
                                             class="h-48 w-48 rounded-lg bg-white object-contain ring-1 ring-slate-200" />
                                    @else
                                        <div class="flex h-48 w-48 flex-col items-center justify-center rounded-lg border border-dashed border-slate-300 bg-white">
                                            <svg class="h-10 w-10 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                                            </svg>
                                            <p class="mt-2 text-xs text-slate-500">QR coming soon</p>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            {{-- Account details --}}
                            <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                                <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Or transfer to
                                </div>
                                <div class="mt-3 space-y-2 text-sm">
                                    <div>
                                        <div class="text-xs text-slate-500">Bank</div>
                                        <div class="font-semibold text-slate-900">{{ $bank['bank_name'] }}</div>
                                    </div>
                                    <div>
                                        <div class="text-xs text-slate-500">Account Name</div>
                                        <div class="font-semibold text-slate-900">{{ $bank['account_name'] }}</div>
                                    </div>
                                </div>
                                <p class="mt-3 text-[11px] leading-relaxed text-slate-500">
                                    Open your {{ $bank['short_name'] ?? $bank['bank_name'] }} app and scan the QR
                                    on the left to pay. The account is automatically filled from the QR.
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800">
                Top-up range: <strong>Nu. {{ number_format($settings['min_topup'], 0) }} – {{ number_format($settings['max_topup'], 0) }}</strong>.
                After transferring, take a screenshot of the success page and upload it below.
            </div>
        @endif
    </div>

    {{-- ─── Upload form ────────────────────────────────────── --}}
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-sm font-bold text-slate-900">Submit Payment Proof</h2>
        <p class="mt-1 text-xs text-slate-500">
            We'll verify the screenshot and credit your wallet — usually within a few hours.
        </p>

        <form wire:submit="submit" class="mt-4 space-y-4">

            <div>
                <label class="mb-1 block text-xs font-medium text-slate-600">
                    Payment Screenshot <span class="text-rose-500">*</span>
                </label>

                <div class="flex items-start gap-3">
                    <div class="shrink-0">
                        @if ($screenshot)
                            <img src="{{ $screenshot->temporaryUrl() }}"
                                 class="h-24 w-24 rounded-lg object-cover ring-1 ring-slate-200" />
                        @else
                            <div class="flex h-24 w-24 items-center justify-center rounded-lg border border-dashed border-slate-300 bg-slate-50 text-slate-400">
                                <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                        @endif
                    </div>
                    <div class="flex-1">
                        <input type="file" wire:model="screenshot" accept="image/*"
                               class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-100 file:px-4 file:py-2 file:text-sm file:font-medium file:text-brand-700 hover:file:bg-brand-200" />
                        <p class="mt-1 text-[11px] text-slate-400">JPEG, PNG or WebP · max 5 MB</p>
                        @error('screenshot') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        <div wire:loading wire:target="screenshot" class="mt-1 text-xs text-brand-600">
                            Uploading…
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">
                        Amount Transferred (Nu.) <span class="text-rose-500">*</span>
                    </label>
                    <input type="number" step="0.01"
                           wire:model="claimedAmount"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                    @error('claimedAmount') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">
                        Bank Reference (optional)
                    </label>
                    <input type="text" wire:model="bankReference"
                           placeholder="e.g. MB2026091900123"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                    @error('bankReference') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-slate-600">Note (optional)</label>
                <textarea wire:model="note" rows="2"
                          placeholder="Anything we should know…"
                          class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"></textarea>
                @error('note') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>

            <button type="submit"
                    wire:loading.attr="disabled" wire:target="submit,screenshot"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-brand-500 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-brand-600 disabled:opacity-50 sm:w-auto sm:px-8">
                <svg wire:loading wire:target="submit" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                <span wire:loading.remove wire:target="submit">Submit for review</span>
                <span wire:loading wire:target="submit">Submitting…</span>
            </button>
        </form>
    </div>

    {{-- ─── History ────────────────────────────────────────── --}}
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-3">
            <h2 class="text-sm font-bold text-slate-900">Recent Submissions</h2>
        </div>

        @if ($proofs->isEmpty())
            <div class="p-8 text-center">
                <p class="text-sm text-slate-500">No submissions yet. Upload your first top-up above.</p>
            </div>
        @else
            <div class="divide-y divide-slate-100">
                @foreach ($proofs as $proof)
                    <div class="flex items-center gap-3 px-5 py-3">
                        @if ($proof->hasMedia('screenshot'))
                            <a href="{{ $proof->getFirstMediaUrl('screenshot') }}" target="_blank"
                               class="shrink-0">
                                <img src="{{ $proof->getFirstMediaUrl('screenshot') }}"
                                     alt="Screenshot"
                                     class="h-12 w-12 rounded-lg object-cover ring-1 ring-slate-200" />
                            </a>
                        @else
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-400">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01"/>
                                </svg>
                            </div>
                        @endif

                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <span class="font-semibold text-slate-900">Nu. {{ number_format($proof->claimed_amount, 2) }}</span>
                                <x-admin.badge :variant="$proof->statusVariant()">{{ $proof->statusLabel() }}</x-admin.badge>
                            </div>
                            <div class="text-xs text-slate-500">
                                {{ $proof->created_at->format('M j, Y · H:i') }}
                                @if ($proof->bank_reference)
                                    · Ref: {{ $proof->bank_reference }}
                                @endif
                            </div>
                            @if ($proof->isRejected() && $proof->rejection_reason)
                                <div class="mt-1 text-xs text-rose-600">Rejected: {{ $proof->rejection_reason }}</div>
                            @endif
                            @if ($proof->isApproved() && $proof->walletTransaction)
                                <div class="mt-1 text-xs text-emerald-600">
                                    Credited — new balance Nu. {{ number_format($proof->walletTransaction->balance_after, 2) }}
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>