<x-layouts::auth :title="__('Register')">
    <div class="flex flex-col gap-6">
        <x-auth-header
            :title="__('Create your account')"
            :description="__('Sign up with your mobile number — no email required')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-6">
            @csrf

            <!-- Name -->
            <flux:input
                name="name"
                :label="__('Full Name')"
                :value="old('name')"
                type="text"
                required
                autofocus
                autocomplete="name"
                :placeholder="__('e.g. Sonam Wangchuk')"
            />

            <!-- Mobile -->
            <flux:input
                name="mobile"
                :label="__('Mobile Number')"
                :value="old('mobile')"
                type="tel"
                inputmode="numeric"
                pattern="[0-9]{8}"
                maxlength="8"
                required
                autocomplete="tel"
                placeholder="17123456"
                description="8 digits, no country code"
            />

            <!-- Password -->
            <flux:input
                name="password"
                :label="__('Password')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Password')"
                passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                viewable
            />

            <!-- Confirm Password -->
            <flux:input
                name="password_confirmation"
                :label="__('Confirm Password')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Confirm password')"
                passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                viewable
            />

            <div class="flex items-center justify-end">
                <flux:button type="submit" variant="primary" class="w-full" data-test="register-user-button">
                    {{ __('Create account') }}
                </flux:button>
            </div>
        </form>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-600 dark:text-zinc-400">
            <span>{{ __('Already have an account?') }}</span>
            <flux:link :href="route('login')" wire:navigate>{{ __('Log in') }}</flux:link>
        </div>
    </div>
</x-layouts::auth>