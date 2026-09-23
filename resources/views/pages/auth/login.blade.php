<x-layouts.auth :title="__('Sign in')">
    <div class="space-y-6">

        <div class="text-center">
            <h1 class="text-xl font-bold text-slate-900">Welcome back</h1>
            <p class="mt-1 text-sm text-slate-500">Sign in to place your lunch order.</p>
        </div>

        {{-- Session status (e.g. password reset success) --}}
        @if (session('status'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('login.store') }}" class="space-y-4">
            @csrf

            {{-- Mobile or Email --}}
            <div>
                <label for="email" class="mb-1 block text-xs font-medium text-slate-600">
                    Mobile or Email
                </label>
                <input type="text"
                       id="email"
                       name="email"
                       value="{{ old('email') }}"
                       required
                       autofocus
                       autocomplete="username"
                       inputmode="text"
                       placeholder="17123456"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                <p class="mt-1 text-[11px] text-slate-400">8-digit mobile, no country code</p>
                @error('email')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Password --}}
            <div>
                <div class="mb-1 flex items-center justify-between">
                    <label for="password" class="block text-xs font-medium text-slate-600">Password</label>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}"
                           class="text-xs font-medium text-brand-600 hover:text-brand-700">
                            Forgot password?
                        </a>
                    @endif
                </div>
                <input type="password"
                       id="password"
                       name="password"
                       required
                       autocomplete="current-password"
                       placeholder="Your password"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                @error('password')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Remember me --}}
            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="remember" value="1"
                       class="h-4 w-4 rounded border-slate-300 text-brand-500 focus:ring-brand-500" />
                Remember me on this device
            </label>

            {{-- Submit --}}
            <button type="submit"
                    class="w-full rounded-lg bg-brand-500 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">
                Sign in
            </button>
        </form>

        {{-- Register link --}}
        @if (Route::has('register'))
            <p class="text-center text-sm text-slate-600">
                Don't have an account?
                <a href="{{ route('register') }}" class="font-semibold text-brand-600 hover:text-brand-700">
                    Create one
                </a>
            </p>
        @endif
    </div>
</x-layouts.auth>