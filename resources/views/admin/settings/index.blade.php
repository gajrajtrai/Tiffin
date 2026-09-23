@php
    $tabs = [
        ['key' => 'general',  'label' => 'General',       'icon' => 'home'],
        ['key' => 'ordering', 'label' => 'Ordering',      'icon' => 'receipt'],
        ['key' => 'wallet',   'label' => 'Wallet',        'icon' => 'credit-card'],
        ['key' => 'landing',  'label' => 'Landing Page',  'icon' => 'book'],
        ['key' => 'banks',    'label' => 'Banks & QRs',   'icon' => 'banknotes'],
    ];
    $canEdit = auth()->user()->can('settings.edit');
@endphp

<div class="space-y-6">

    {{-- ─── Header ─────────────────────────────────────────── --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Restaurant Settings</h1>
            <p class="mt-1 text-sm text-slate-500">Changes apply immediately across the app.</p>
        </div>
        @if ($canEdit)
            <button type="button" wire:click="save"
                    wire:loading.attr="disabled" wire:target="save"
                    class="inline-flex items-center gap-2 self-start rounded-lg bg-brand-500 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-600 disabled:opacity-50 sm:self-auto">
                <svg wire:loading.remove wire:target="save" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <svg wire:loading wire:target="save" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                <span wire:loading.remove wire:target="save">Save changes</span>
                <span wire:loading wire:target="save">Saving…</span>
            </button>
        @endif
    </div>

    @if ($statusMessage)
        <x-admin.alert :type="$statusType">{{ $statusMessage }}</x-admin.alert>
    @endif

    {{-- ─── Two-pane ───────────────────────────────────────── --}}
    <div class="grid gap-4 lg:grid-cols-4">

        {{-- Tabs sidebar --}}
        <aside class="h-fit rounded-xl border border-slate-200 bg-white p-2 shadow-sm">
            <nav class="space-y-1">
                @foreach ($tabs as $tab)
                    <button type="button" wire:click="switchTab('{{ $tab['key'] }}')"
                            @class([
                                'flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm font-medium transition',
                                'bg-brand-500 text-white shadow-sm' => $activeTab === $tab['key'],
                                'text-slate-600 hover:bg-slate-100' => $activeTab !== $tab['key'],
                            ])>
                        <x-admin.icon :name="$tab['icon']" class="h-4 w-4 shrink-0" />
                        {{ $tab['label'] }}
                    </button>
                @endforeach
            </nav>
        </aside>

        {{-- Panel --}}
        <div class="space-y-5 lg:col-span-3">

            {{-- ═══ GENERAL ═══ --}}
            @if ($activeTab === 'general')
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-sm font-bold text-slate-900">General</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Basic information customers see on receipts and pages.</p>

                    <div class="mt-5 space-y-4">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-xs font-medium text-slate-600">Restaurant Name <span class="text-rose-500">*</span></label>
                                <input type="text" wire:model="restaurant_name"
                                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                                @error('restaurant_name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-slate-600">Contact Mobile <span class="text-rose-500">*</span></label>
                                <input type="text" wire:model="restaurant_mobile" placeholder="17123456"
                                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                                <p class="mt-1 text-[11px] text-slate-400">8 digits, no country code</p>
                                @error('restaurant_mobile') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-600">Address <span class="text-rose-500">*</span></label>
                            <input type="text" wire:model="restaurant_address"
                                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                            @error('restaurant_address') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>
            @endif

            {{-- ═══ ORDERING ═══ --}}
            @if ($activeTab === 'ordering')
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-sm font-bold text-slate-900">Ordering</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Controls when customers can order and how many orders you accept.</p>

                    <div class="mt-5 space-y-4">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-xs font-medium text-slate-600">Delivery Cut-off Time <span class="text-rose-500">*</span></label>
                                <input type="text" wire:model="order_cutoff_time" placeholder="11:00"
                                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                                <p class="mt-1 text-[11px] text-slate-400">24-hour HH:MM format. Delivery orders close at this time; pickup remains open.</p>
                                @error('order_cutoff_time') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-slate-600">Delivery Slot Label <span class="text-rose-500">*</span></label>
                                <input type="text" wire:model="delivery_slot_label" placeholder="11:00 AM – 2:00 PM"
                                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                                <p class="mt-1 text-[11px] text-slate-400">Shown to customers on the order confirmation.</p>
                                @error('delivery_slot_label') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-xs font-medium text-slate-600">Max Delivery Orders per Day <span class="text-rose-500">*</span></label>
                                <input type="number" min="1" max="10000" wire:model="delivery_capacity"
                                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                                @error('delivery_capacity') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-slate-600">Max Main Courses per Day <span class="text-rose-500">*</span></label>
                                <input type="number" min="1" max="10" wire:model="max_mains_per_day"
                                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                                <p class="mt-1 text-[11px] text-slate-400">Enforced when publishing daily menus.</p>
                                @error('max_mains_per_day') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- ═══ WALLET ═══ --}}
            @if ($activeTab === 'wallet')
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-sm font-bold text-slate-900">Wallet</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Top-up limits and low-balance alerts.</p>

                    <div class="mt-5 space-y-4">
                        <div class="grid gap-4 sm:grid-cols-3">
                            <div>
                                <label class="mb-1 block text-xs font-medium text-slate-600">Minimum Top-up (Nu.) <span class="text-rose-500">*</span></label>
                                <input type="number" step="0.01" min="1" wire:model="wallet_min_topup"
                                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                                @error('wallet_min_topup') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-slate-600">Maximum Top-up (Nu.) <span class="text-rose-500">*</span></label>
                                <input type="number" step="0.01" min="1" wire:model="wallet_max_topup"
                                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                                @error('wallet_max_topup') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-slate-600">Low Balance Alert (Nu.) <span class="text-rose-500">*</span></label>
                                <input type="number" step="0.01" min="0" wire:model="low_balance_threshold"
                                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                                <p class="mt-1 text-[11px] text-slate-400">Customers below this balance get flagged on the dashboard.</p>
                                @error('low_balance_threshold') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- ═══ LANDING PAGE ═══ --}}
            @if ($activeTab === 'landing')
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-sm font-bold text-slate-900">Landing Page</h2>
                    <p class="mt-0.5 text-xs text-slate-500">The text customers see at the top of the home page.</p>

                    <div class="mt-5 space-y-4">
                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-600">Hero Headline <span class="text-rose-500">*</span></label>
                            <input type="text" wire:model.live.debounce.300ms="landing_hero_title"
                                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                            @error('landing_hero_title') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-600">Hero Subtitle <span class="text-rose-500">*</span></label>
                            <textarea wire:model.live.debounce.300ms="landing_hero_subtitle" rows="3"
                                      class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"></textarea>
                            @error('landing_hero_subtitle') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>

                        {{-- Live preview --}}
                        <div class="rounded-lg border border-slate-200 bg-gradient-to-b from-brand-50 to-cream-50 p-4">
                            <div class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">Live Preview</div>
                            <h3 class="mt-2 text-xl font-bold leading-tight text-slate-900">
                                {{ $landing_hero_title }}
                            </h3>
                            <p class="mt-1 text-sm text-slate-600">
                                {{ $landing_hero_subtitle }}
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- ═══ BANKS & QRs ═══ --}}
            @if ($activeTab === 'banks')
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex items-start justify-between">
                        <div>
                            <h2 class="text-sm font-bold text-slate-900">Bank Accounts & QRs</h2>
                            <p class="mt-0.5 text-xs text-slate-500">
                                Each bank appears as a tab on the customer's wallet page.
                            </p>
                        </div>
                        <button type="button" wire:click="addBank"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-brand-500 bg-white px-3 py-1.5 text-sm font-medium text-brand-600 hover:bg-brand-50">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Add Bank
                        </button>
                    </div>

                    @if (empty($banks))
                        <div class="mt-5 rounded-lg border border-dashed border-slate-300 bg-slate-50 p-8 text-center">
                            <p class="text-sm text-slate-600">No banks configured yet.</p>
                            <p class="mt-1 text-xs text-slate-400">Click <strong>Add Bank</strong> to set up your first one.</p>
                        </div>
                    @else
                        <div class="mt-5 space-y-5">
                            @foreach ($banks as $index => $bank)
                                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4" wire:key="bank-{{ $index }}">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">
                                            Bank {{ $index + 1 }}
                                        </div>
                                        <button type="button" wire:click="removeBank({{ $index }})"
                                                wire:confirm="Remove this bank from the list?"
                                                class="rounded p-1 text-slate-400 hover:bg-rose-50 hover:text-rose-600">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>

                                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                        <div>
                                            <label class="mb-1 block text-xs font-medium text-slate-600">Bank Name <span class="text-rose-500">*</span></label>
                                            <input type="text" wire:model="banks.{{ $index }}.bank_name"
                                                   placeholder="Bank of Bhutan"
                                                   class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                                            @error("banks.{$index}.bank_name") <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-xs font-medium text-slate-600">Short Label <span class="text-rose-500">*</span></label>
                                            <input type="text" wire:model="banks.{{ $index }}.short_name"
                                                   placeholder="BoB"
                                                   class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                                            <p class="mt-1 text-[11px] text-slate-400">Shown as the tab label.</p>
                                            @error("banks.{$index}.short_name") <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                                        </div>
                                    </div>

                                    <div class="mt-3">
                                        <label class="mb-1 block text-xs font-medium text-slate-600">Account Holder Name <span class="text-rose-500">*</span></label>
                                        <input type="text" wire:model="banks.{{ $index }}.account_name"
                                               class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                                        @error("banks.{$index}.account_name") <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                                    </div>

                                    {{-- QR upload --}}
                                    <div class="mt-3">
                                        <label class="mb-1 block text-xs font-medium text-slate-600">Merchant QR Image</label>

                                        <div class="flex items-start gap-3">
                                            <div class="shrink-0">
                                                @php
                                                    $qr = $banks[$index]['qr_image'] ?? '';
                                                    $qrExists = $qr && file_exists(public_path('qr/'.$qr));
                                                @endphp

                                                @if (isset($qrUploads[$index]) && $qrUploads[$index])
                                                    <img src="{{ $qrUploads[$index]->temporaryUrl() }}"
                                                         class="h-24 w-24 rounded-lg bg-white object-contain ring-1 ring-slate-200" />
                                                @elseif ($qrExists)
                                                    <img src="{{ asset('qr/'.$qr) }}"
                                                         class="h-24 w-24 rounded-lg bg-white object-contain ring-1 ring-slate-200" />
                                                @else
                                                    <div class="flex h-24 w-24 items-center justify-center rounded-lg border border-dashed border-slate-300 bg-white text-slate-400">
                                                        <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                                                        </svg>
                                                    </div>
                                                @endif
                                            </div>

                                            <div class="flex-1">
                                                <input type="file" wire:model="qrUploads.{{ $index }}" accept="image/*"
                                                       class="block w-full text-xs text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-100 file:px-3 file:py-1.5 file:text-xs file:font-medium file:text-brand-700 hover:file:bg-brand-200" />
                                                <p class="mt-1 text-[11px] text-slate-400">JPEG, PNG or WebP · max 2 MB</p>
                                                @error("qrUploads.{$index}") <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror

                                                @if ($qrExists || (isset($qrUploads[$index]) && $qrUploads[$index]))
                                                    <button type="button" wire:click="removeQr({{ $index }})"
                                                            class="mt-2 text-xs font-medium text-rose-600 hover:text-rose-700">
                                                        Remove QR
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif

        </div>
    </div>
</div>