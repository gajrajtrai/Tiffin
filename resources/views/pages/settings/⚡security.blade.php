<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Password')] #[Layout('layouts.settings')] class extends Component {
    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    public ?string $statusMessage = null;

    public function updatePassword(): void
    {
        $this->validate([
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'string', 'confirmed', Password::min(8)],
        ], [
            'current_password.required' => 'Enter your current password.',
            'password.required'         => 'Choose a new password.',
            'password.confirmed'        => 'The password confirmation does not match.',
            'password.min'              => 'Password must be at least 8 characters.',
        ]);

        $user = Auth::user();

        if (! Hash::check($this->current_password, $user->password)) {
            $this->addError('current_password', 'The current password is incorrect.');
            return;
        }

        $user->password = Hash::make($this->password);
        $user->save();

        $this->reset(['current_password', 'password', 'password_confirmation']);
        $this->statusMessage = 'Password updated successfully.';
    }
}; ?>

<div>
    <x-pages::settings.layout heading="Password" subheading="Change the password you use to sign in">

        @if ($statusMessage)
            <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800">
                {{ $statusMessage }}
            </div>
        @endif

        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <form wire:submit="updatePassword" class="space-y-5">

                {{-- Current password --}}
                <div>
                    <label for="current_password" class="mb-1 block text-xs font-medium text-slate-600">
                        Current Password
                    </label>
                    <input type="password"
                           id="current_password"
                           wire:model="current_password"
                           required
                           autocomplete="current-password"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                    @error('current_password') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                {{-- New password --}}
                <div>
                    <label for="password" class="mb-1 block text-xs font-medium text-slate-600">
                        New Password
                    </label>
                    <input type="password"
                           id="password"
                           wire:model="password"
                           required
                           autocomplete="new-password"
                           placeholder="At least 8 characters"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                    @error('password') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                {{-- Confirm --}}
                <div>
                    <label for="password_confirmation" class="mb-1 block text-xs font-medium text-slate-600">
                        Confirm New Password
                    </label>
                    <input type="password"
                           id="password_confirmation"
                           wire:model="password_confirmation"
                           required
                           autocomplete="new-password"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                </div>

                <div class="flex justify-end pt-2">
                    <button type="submit"
                            wire:loading.attr="disabled" wire:target="updatePassword"
                            class="rounded-lg bg-brand-500 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-600 disabled:opacity-50">
                        <span wire:loading.remove wire:target="updatePassword">Update password</span>
                        <span wire:loading wire:target="updatePassword">Updating…</span>
                    </button>
                </div>
            </form>
        </div>

        {{-- Note about 2FA / passkeys --}}
        <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4 text-xs text-slate-500">
            <strong class="text-slate-700">Coming soon:</strong>
            Two-factor authentication and passkey sign-in are planned for a future release.
        </div>
    </x-pages::settings.layout>
</div>