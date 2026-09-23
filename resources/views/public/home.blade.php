@use('App\Modules\Core\Models\Setting')
@use('App\Modules\Menu\Models\ServiceDay')

@php
    $heroTitle    = Setting::get('landing_hero_title', 'Fresh lunch from Tamkulay Tiffins');
    $heroSubtitle = Setting::get('landing_hero_subtitle', 'Home-style meals delivered to your college gate or ready for pickup at our counter. Prepaid wallet, no queues, no fuss.');
    $restaurant   = Setting::get('restaurant_name', config('app.name'));
    $serviceDay   = ServiceDay::forDate(today());
    $cutoffTime   = $serviceDay->effectiveCutoffTime();

    // Low-balance banner state
    $customer = auth()->user();
    $showLowBalance = $customer
        && $customer->hasRole('Customer')
        && $customer->hasLowBalance();

    // How much to top up to just clear the threshold
    $shortfallToThreshold = $showLowBalance
        ? max(0, (float) $customer->low_balance_threshold - (float) $customer->wallet_balance)
        : 0;
@endphp

<x-layouts.public title="{{ $restaurant }} · Fresh Tiffins, Delivered">

    {{-- ─── Low-balance reminder ───────────────────────────── --}}
    @if ($showLowBalance)
        <div class="border-b border-amber-200 bg-amber-50">
            <div class="mx-auto flex max-w-5xl items-start gap-3 px-4 py-3 sm:items-center">

                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>

                <div class="min-w-0 flex-1">
                    <div class="text-sm font-semibold text-amber-900">
                        Your wallet balance is running low
                    </div>
                    <div class="mt-0.5 text-xs text-amber-800">
                        Balance: <strong>Nu. {{ number_format((float) $customer->wallet_balance, 2) }}</strong>
                        @if ($shortfallToThreshold > 0)
                            · Top up <strong>Nu. {{ number_format($shortfallToThreshold, 0) }}</strong>
                            or more to stay above the alert threshold
                        @endif
                    </div>
                </div>

                <a href="{{ route('wallet.index') }}"
                   class="shrink-0 rounded-lg bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-amber-700">
                    Top up
                </a>
            </div>
        </div>
    @endif

    {{-- ─── Hero ───────────────────────────────────────────── --}}
    <section class="bg-gradient-to-b from-brand-50 to-cream-50">
        <div class="mx-auto max-w-5xl px-4 py-10 sm:py-16">
            @if ($serviceDay->is_open)
                <span class="inline-flex items-center gap-1 rounded-full bg-brand-100 px-3 py-1 text-xs font-medium text-brand-700">
                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Order cut-off today: {{ $cutoffTime }}
                    @if ($serviceDay->isPastCutoff())
                        · <span class="font-semibold text-amber-700">Cut-off passed — pickup still available</span>
                    @endif
                </span>
            @else
                <span class="inline-flex items-center gap-1 rounded-full bg-rose-100 px-3 py-1 text-xs font-medium text-rose-700">
                    Closed today · Back tomorrow
                </span>
            @endif

            <h1 class="mt-4 text-3xl font-bold leading-tight text-slate-900 sm:text-5xl">
                {{ $heroTitle }}
            </h1>
            <p class="mt-3 max-w-xl text-base text-slate-600 sm:text-lg">
                {{ $heroSubtitle }}
            </p>

            <div class="mt-6 flex flex-wrap gap-3">
                <a href="{{ route('menu.index') }}"
                   class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-brand-600">
                    View today's menu
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
                @guest
                    <a href="{{ route('register') }}"
                       class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:border-brand-300 hover:text-brand-600">
                        Create account
                    </a>
                @else
                    @if (auth()->user()->hasRole('Customer'))
                        <a href="{{ route('wallet.index') }}"
                           class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:border-brand-300 hover:text-brand-600">
                            My wallet · Nu. {{ number_format(auth()->user()->wallet_balance, 0) }}
                        </a>
                    @endif
                @endguest
            </div>
        </div>
    </section>

    {{-- ─── How it works ──────────────────────────────────── --}}
    <section class="mx-auto max-w-5xl px-4 py-10">
        <h2 class="text-lg font-bold text-slate-900 sm:text-xl">How it works</h2>
        <div class="mt-5 grid gap-4 sm:grid-cols-3">
            @php
                $steps = [
                    ['icon' => 'users', 'title' => '1. Register', 'text' => 'Sign up with your 8-digit mobile number in under a minute.'],
                    ['icon' => 'banknotes', 'title' => '2. Top up wallet', 'text' => 'Scan our QR, pay, upload the screenshot — we credit your wallet.'],
                    ['icon' => 'receipt', 'title' => '3. Order & enjoy', 'text' => 'Pick your meal for the day. Delivered to the gate or pickup at counter.'],
                ];
            @endphp

            @foreach ($steps as $step)
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-brand-100 text-brand-600">
                        <x-admin.icon :name="$step['icon']" class="h-5 w-5" />
                    </div>
                    <h3 class="mt-3 text-sm font-bold text-slate-900">{{ $step['title'] }}</h3>
                    <p class="mt-1 text-sm text-slate-600">{{ $step['text'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ─── Info strip ────────────────────────────────────── --}}
    <section class="mx-auto max-w-5xl px-4 pb-10">
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-5">
                <h3 class="flex items-center gap-2 text-sm font-bold text-emerald-800">
                    <x-admin.icon name="truck" class="h-4 w-4" />
                    Delivery
                </h3>
                <p class="mt-1 text-sm text-emerald-700">
                    Lunch only · cut-off at {{ $cutoffTime }} · delivered to college gate.
                </p>
            </div>
            <div class="rounded-xl border border-sky-200 bg-sky-50 p-5">
                <h3 class="flex items-center gap-2 text-sm font-bold text-sky-800">
                    <x-admin.icon name="home" class="h-4 w-4" />
                    Pickup
                </h3>
                <p class="mt-1 text-sm text-sky-700">
                    Any time during opening hours · our counter, near the college.
                </p>
            </div>
        </div>
    </section>
</x-layouts.public>