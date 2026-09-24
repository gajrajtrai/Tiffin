<?php

use App\Models\AccountClosureRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Profile settings')] #[Layout('layouts.settings')] class extends Component {
    public string $name = '';
    public ?string $email = null;
    public string $mobile = '';

    public ?string $statusMessage = null;

    // Closure request form
    public string $closureReason = '';
    public ?string $closureStatusMessage = null;

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
            'mobile.regex'  => 'Mobile must be 8 digits (e.g. 17111101).',
            'mobile.unique' => 'This mobile number is already registered.',
        ]);

        $validated['email'] = $validated['email'] ?: null;
        $validated['mobile'] = $validated['mobile'] ?: null;

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->statusMessage = 'Profile updated.';
    }

    public function requestClosure(): void
    {
        $user = Auth::user();

        // One active request at a time
        $existing = AccountClosureRequest::query()
            ->where('user_id', $user->id)
            ->pending()
            ->exists();

        if ($existing) {
            $this->closureStatusMessage = 'You already have a pending closure request.';
            return;
        }

        $this->validate([
            'closureReason' => ['nullable', 'string', 'max:500'],
        ]);

        AccountClosureRequest::create([
            'user_id' => $user->id,
            'reason'  => $this->closureReason !== '' ? $this->closureReason : null,
            'status'  => AccountClosureRequest::STATUS_PENDING,
        ]);

        $this->reset('closureReason');
        $this->closureStatusMessage = 'Request submitted. We will review it and be in touch.';
    }

    #[Computed]
    public function hasUnverifiedEmail(): bool
    {
        return Auth::user() instanceof MustVerifyEmail
            && Auth::user()->email !== null
            && ! Auth::user()->hasVerifiedEmail();
    }

    #[Computed]
    public function latestClosureRequest(): ?AccountClosureRequest
    {
        return AccountClosureRequest::query()
            ->where('user_id', Auth::id())
            ->latest()
            ->first();
    }
}; ?>

<div>
    <x-pages::settings.layout heading="Profile" subheading="Update your name, mobile, and email">

        @if ($statusMessage)
            <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800">
                {{ $statusMessage }}
            </div>
        @endif

        {{-- ─── Profile form ───────────────────────────────── --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <form wire:submit="updateProfileInformation" class="space-y-5">

                <div>
                    <label for="name" class="mb-1 block text-xs font-medium text-slate-600">Full Name</label>
                    <input type="text" id="name" wire:model="name" required autofocus autocomplete="name"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                    @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="mobile" class="mb-1 block text-xs font-medium text-slate-600">Mobile Number</label>
                    <input type="tel" id="mobile" wire:model="mobile" inputmode="numeric" maxlength="8"
                           placeholder="17123456" autocomplete="tel"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                    <p class="mt-1 text-[11px] text-slate-400">8 digits, no country code</p>
                    @error('mobile') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="email" class="mb-1 block text-xs font-medium text-slate-600">Email (optional)</label>
                    <input type="email" id="email" wire:model="email" autocomplete="email"
                           placeholder="you@example.com"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                    <p class="mt-1 text-[11px] text-slate-400">Used only for password reset. You can leave this blank.</p>
                    @error('email') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror

                    @if ($this->hasUnverifiedEmail)
                        <div class="mt-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                            Your email is unverified.
                        </div>
                    @endif
                </div>

                <div class="flex justify-end pt-2">
                    <button type="submit"
                            wire:loading.attr="disabled" wire:target="updateProfileInformation"
                            class="rounded-lg bg-brand-500 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-600 disabled:opacity-50">
                        <span wire:loading.remove wire:target="updateProfileInformation">Save changes</span>
                        <span wire:loading wire:target="updateProfileInformation">Saving…</span>
                    </button>
                </div>
            </form>
        </div>

        {{-- ─── Close account section ──────────────────────── --}}
        <div class="mt-6 rounded-xl border border-rose-200 bg-rose-50 p-6">
            <div class="flex items-start gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-rose-100 text-rose-700">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <h3 class="text-sm font-bold text-rose-900">Close your account</h3>
                    <p class="mt-1 text-xs text-rose-800">
                        Requesting closure will suspend your account and prevent new logins.
                        Your order history and wallet records are preserved.
                        Our team will review your request and be in touch.
                    </p>

                    @if ($closureStatusMessage)
                        <div class="mt-3 rounded-lg border border-rose-300 bg-white px-3 py-2 text-xs text-rose-800">
                            {{ $closureStatusMessage }}
                        </div>
                    @endif

                    @php $latest = $this->latestClosureRequest; @endphp

                    @if ($latest && $latest->isPending())
                        <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                            <strong>Pending request</strong> submitted {{ $latest->created_at->diffForHumans() }}.
                            Our team will review it shortly.
                        </div>
                    @elseif ($latest && $latest->status === \App\Models\AccountClosureRequest::STATUS_REJECTED && $latest->reviewed_at?->gt(now()->subDays(30)))
                        <div class="mt-3 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs text-slate-700">
                            <strong>A previous request was declined</strong> on
                            {{ $latest->reviewed_at->format('M j, Y') }}.
                            @if ($latest->admin_notes)
                                <div class="mt-1">{{ $latest->admin_notes }}</div>
                            @endif
                        </div>
                    @else
                        <form wire:submit="requestClosure" class="mt-4 space-y-3">
                            <div>
                                <label for="closureReason" class="mb-1 block text-xs font-medium text-rose-900">
                                    Reason (optional)
                                </label>
                                <textarea id="closureReason" wire:model="closureReason" rows="2"
                                          placeholder="e.g. Moving out of town, no longer ordering"
                                          class="w-full rounded-lg border border-rose-300 bg-white px-3 py-2 text-sm focus:border-rose-500 focus:outline-none focus:ring-1 focus:ring-rose-500"></textarea>
                                @error('closureReason') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                            </div>
                            <button type="submit"
                                    wire:loading.attr="disabled" wire:target="requestClosure"
                                    wire:confirm="Are you sure you want to request account closure?"
                                    class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-rose-700 disabled:opacity-50">
                                <span wire:loading.remove wire:target="requestClosure">Request account closure</span>
                                <span wire:loading wire:target="requestClosure">Submitting…</span>
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </x-pages::settings.layout>
</div>