@php
    $user = auth()->user();
    $isCustomer = $user?->hasRole('Customer') ?? false;
    $isStaff = $user?->isStaff() ?? false;
@endphp

<header x-data="{ mobileOpen: false }" class="sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur">
    <div class="mx-auto flex h-14 max-w-5xl items-center gap-2 px-4">

        {{-- Brand --}}
        <a href="{{ url('/') }}" class="flex items-center gap-2 shrink-0">
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-500 text-white">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
            </div>
            <span class="hidden text-sm font-bold text-slate-900 sm:inline">{{ config('app.name') }}</span>
        </a>

        {{-- Center nav (desktop) --}}
        <nav class="ml-2 hidden items-center gap-1 sm:flex">
            <a href="{{ route('menu.index') }}"
               @class([
                   'rounded-lg px-3 py-1.5 text-sm font-medium transition',
                   'bg-brand-50 text-brand-700' => request()->is('menu*'),
                   'text-slate-600 hover:bg-slate-100 hover:text-slate-900' => ! request()->is('menu*'),
               ])>
                Menu
            </a>
            @if ($isCustomer)
                <a href="{{ route('orders.index') }}"
                   @class([
                       'rounded-lg px-3 py-1.5 text-sm font-medium transition',
                       'bg-brand-50 text-brand-700' => request()->is('orders*'),
                       'text-slate-600 hover:bg-slate-100 hover:text-slate-900' => ! request()->is('orders*'),
                   ])>
                    Orders
                </a>
                <a href="{{ route('wallet.index') }}"
                   @class([
                       'rounded-lg px-3 py-1.5 text-sm font-medium transition',
                       'bg-brand-50 text-brand-700' => request()->is('wallet*'),
                       'text-slate-600 hover:bg-slate-100 hover:text-slate-900' => ! request()->is('wallet*'),
                   ])>
                    Wallet
                </a>
            @endif
        </nav>

        <div class="flex-1"></div>

        {{-- Mobile nav toggle --}}
        <button @click="mobileOpen = !mobileOpen"
                type="button"
                class="flex h-9 w-9 items-center justify-center rounded-lg text-slate-600 hover:bg-slate-100 sm:hidden"
                :aria-expanded="mobileOpen.toString()"
                aria-controls="mobile-nav-panel"
                aria-label="Toggle navigation menu">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path x-show="!mobileOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                <path x-show="mobileOpen" x-cloak stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>

        @if ($user)
            {{-- Wallet pill (desktop) --}}
            @if ($isCustomer)
                <a href="{{ route('wallet.index') }}"
                   class="hidden items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-700 hover:bg-emerald-100 sm:flex">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z"/>
                    </svg>
                    Nu. {{ number_format($user->wallet_balance ?? 0, 2) }}
                </a>
            @endif

            {{-- User menu --}}
            <div x-data="{ open: false }" @keydown.escape.window="open = false" class="relative shrink-0">
                <button @click="open = !open"
                        type="button"
                        :aria-expanded="open.toString()"
                        aria-haspopup="true"
                        class="flex h-9 w-9 items-center justify-center rounded-full bg-brand-500 text-xs font-bold text-white">
                    {{ $user->initials() }}
                </button>
                <div x-show="open" x-cloak x-transition @click.outside="open = false"
                     role="menu"
                     class="absolute right-0 mt-2 w-56 rounded-lg border border-slate-200 bg-white py-1 shadow-lg">
                    <div class="border-b border-slate-100 px-4 py-2 text-xs text-slate-500">
                        Signed in as<br>
                        <span class="font-medium text-slate-700">{{ $user->name }}</span>
                    </div>
                    @if ($isCustomer)
                        <a href="{{ route('wallet.index') }}" role="menuitem"
                           class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">My Wallet</a>
                    @endif
                    @if ($isStaff)
                        <a href="{{ route('admin.dashboard') }}" role="menuitem"
                           class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Admin Dashboard</a>
                    @endif
                    <a href="{{ url('/settings/profile') }}" role="menuitem"
                       class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Profile</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" role="menuitem"
                                class="block w-full text-left px-4 py-2 text-sm text-rose-600 hover:bg-rose-50">
                            Sign out
                        </button>
                    </form>
                </div>
            </div>
        @else
            <a href="{{ route('login') }}"
               class="shrink-0 rounded-lg bg-brand-500 px-3 py-1.5 text-sm font-medium text-white hover:bg-brand-600">
                Sign in
            </a>
        @endif
    </div>

    {{-- Mobile nav panel --}}
    <div x-show="mobileOpen" x-cloak x-transition id="mobile-nav-panel" class="border-t border-slate-200 bg-white sm:hidden">
        <nav class="mx-auto flex max-w-5xl flex-col gap-1 px-4 py-2">
            <a href="{{ route('menu.index') }}"
               @class([
                   'rounded-lg px-3 py-2 text-sm font-medium transition',
                   'bg-brand-50 text-brand-700' => request()->is('menu*'),
                   'text-slate-600 hover:bg-slate-100' => ! request()->is('menu*'),
               ])>
                Menu
            </a>
            @if ($isCustomer)
                <a href="{{ route('orders.index') }}"
                   @class([
                       'rounded-lg px-3 py-2 text-sm font-medium transition',
                       'bg-brand-50 text-brand-700' => request()->is('orders*'),
                       'text-slate-600 hover:bg-slate-100' => ! request()->is('orders*'),
                   ])>
                    Orders
                </a>
                <a href="{{ route('wallet.index') }}"
                   @class([
                       'rounded-lg px-3 py-2 text-sm font-medium transition',
                       'bg-brand-50 text-brand-700' => request()->is('wallet*'),
                       'text-slate-600 hover:bg-slate-100' => ! request()->is('wallet*'),
                   ])>
                    Wallet · Nu. {{ number_format($user->wallet_balance ?? 0, 2) }}
                </a>
            @endif
        </nav>
    </div>
</header>