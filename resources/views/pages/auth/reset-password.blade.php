<x-layouts.auth :title="__('Set new password')">
    <div class="space-y-6">

        <div class="text-center">
            <h1 class="text-xl font-bold text-slate-900">Set a new password</h1>
            <p class="mt-1 text-sm text-slate-500">
                Choose a new password for your account.
            </p>
        </div>

        {{-- Session status --}}
        @if (session('status'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
            @csrf

            {{-- Hidden token --}}
            <input type="hidden" name="token" value="{{ request()->route('token') }}" />

            {{-- Email --}}
            <div>
                <label for="email" class="mb-1 block text-xs font-medium text-slate-600">
                    Email Address
                </label>
                <input type="email"
                       id="email"
                       name="email"
                       value="{{ old('email', request()->query('email')) }}"
                       required
                       autofocus
                       autocomplete="email"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                @error('email')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Password --}}
            <div>
                <label for="password" class="mb-1 block text-xs font-medium text-slate-600">
                    New Password
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
                       placeholder="Re-enter your new password"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                @error('password_confirmation')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit"
                    class="w-full rounded-lg bg-brand-500 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">
                Reset password
            </button>
        </form>

        <p class="text-center text-sm text-slate-600">
            <a href="{{ route('login') }}" class="font-semibold text-brand-600 hover:text-brand-700">
                Back to sign in
            </a>
        </p>
    </div>
</x-layouts.auth>