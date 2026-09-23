<x-layouts.auth :title="__('Create account')">
    <div class="space-y-6">

        <div class="text-center">
            <h1 class="text-xl font-bold text-slate-900">Create your account</h1>
            <p class="mt-1 text-sm text-slate-500">Sign up with your mobile number. No email needed.</p>
        </div>

        {{-- Session status --}}
        @if (session('status'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('register.store') }}" class="space-y-4">
            @csrf

            {{-- Full name --}}
            <div>
                <label for="name" class="mb-1 block text-xs font-medium text-slate-600">
                    Full Name
                </label>
                <input type="text"
                       id="name"
                       name="name"
                       value="{{ old('name') }}"
                       required
                       autofocus
                       autocomplete="name"
                       placeholder="e.g. Sonam Wangchuk"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                @error('name')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Mobile --}}
            <div>
                <label for="mobile" class="mb-1 block text-xs font-medium text-slate-600">
                    Mobile Number
                </label>
                <input type="tel"
                       id="mobile"
                       name="mobile"
                       value="{{ old('mobile') }}"
                       required
                       inputmode="numeric"
                       pattern="[0-9]{8}"
                       maxlength="8"
                       autocomplete="tel"
                       placeholder="17123456"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                <p class="mt-1 text-[11px] text-slate-400">8 digits, no country code</p>
                @error('mobile')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Password --}}
            <div>
                <label for="password" class="mb-1 block text-xs font-medium text-slate-600">
                    Password
                </label>
                <input type="password"
                       id="password"
                       name="password"
                       required
                       autocomplete="new-password"
                       placeholder="At least 8 characters"
                       passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                @error('password')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Confirm password --}}
            <div>
                <label for="password_confirmation" class="mb-1 block text-xs font-medium text-slate-600">
                    Confirm Password
                </label>
                <input type="password"
                       id="password_confirmation"
                       name="password_confirmation"
                       required
                       autocomplete="new-password"
                       placeholder="Re-enter your password"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                @error('password_confirmation')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Submit --}}
            <button type="submit"
                    class="w-full rounded-lg bg-brand-500 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">
                Create account
            </button>
        </form>

        {{-- Login link --}}
        @if (Route::has('login'))
            <p class="text-center text-sm text-slate-600">
                Already have an account?
                <a href="{{ route('login') }}" class="font-semibold text-brand-600 hover:text-brand-700">
                    Sign in
                </a>
            </p>
        @endif
    </div>
</x-layouts.auth>