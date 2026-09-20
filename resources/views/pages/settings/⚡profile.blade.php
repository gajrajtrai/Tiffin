<?php

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Profile settings')] class extends Component {
    public string $name = '';
    public ?string $email = null;
    public string $mobile = '';

    public function mount(): void
    {
        $user = Auth::user();
        $this->name = (string) $user->name;
        $this->email = $user->email;
        $this->mobile = (string) ($user->mobile ?? '');
    }

    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name'   => ['required', 'string', 'max:255'],
            'email'  => ['nullable', 'string', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'mobile' => ['nullable', 'string', 'regex:/^\d{8}$/', 'unique:users,mobile,'.$user->id],
        ], [
            'mobile.regex' => 'Mobile must be 8 digits (e.g. 17111101).',
            'mobile.unique' => 'This mobile number is already registered.',
        ]);

        // Normalise empty strings to null for nullable columns
        $validated['email'] = $validated['email'] ?: null;
        $validated['mobile'] = $validated['mobile'] ?: null;

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        Flux::toast(variant: 'success', text: __('Profile updated.'));
    }

    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));
            return;
        }

        $user->sendEmailVerificationNotification();
        Session::flash('status', 'verification-link-sent');
    }

    #[Computed]
    public function hasUnverifiedEmail(): bool
    {
        return Auth::user() instanceof MustVerifyEmail
            && Auth::user()->email !== null
            && ! Auth::user()->hasVerifiedEmail();
    }

    #[Computed]
    public function showDeleteUser(): bool
    {
        return ! Auth::user() instanceof MustVerifyEmail
            || (Auth::user() instanceof MustVerifyEmail && Auth::user()->hasVerifiedEmail());
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading level="2" class="sr-only">{{ __('Profile settings') }}</flux:heading>

    <x-pages::settings.layout :heading="__('Profile')" :subheading="__('Update your name, mobile, and email')">
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            <flux:input wire:model="name" :label="__('Name')" type="text" required autofocus autocomplete="name" />

            <flux:input wire:model="mobile"
                        :label="__('Mobile Number')"
                        type="text"
                        placeholder="17123456"
                        autocomplete="tel"
                        description="8 digits, no country code" />

            <div>
                <flux:input wire:model="email"
                            :label="__('Email (optional)')"
                            type="email"
                            autocomplete="email"
                            description="Optional — you can sign in with your mobile number" />

                @if ($this->hasUnverifiedEmail)
                    <div>
                        <flux:text class="mt-4">
                            {{ __('Your email address is unverified.') }}
                            <flux:link class="text-sm cursor-pointer" wire:click.prevent="resendVerificationNotification">
                                {{ __('Click here to re-send the verification email.') }}
                            </flux:link>
                        </flux:text>

                        @if (session('status') === 'verification-link-sent')
                            <flux:text class="mt-2 font-medium !dark:text-green-400 !text-green-600">
                                {{ __('A new verification link has been sent to your email address.') }}
                            </flux:text>
                        @endif
                    </div>
                @endif
            </div>

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <flux:button variant="primary" type="submit" class="w-full" data-test="update-profile-button">
                        {{ __('Save') }}
                    </flux:button>
                </div>
            </div>
        </form>

        @if ($this->showDeleteUser)
            <livewire:pages::settings.delete-user-form />
        @endif
    </x-pages::settings.layout>
</section>