<div class="mx-auto max-w-3xl space-y-6">

    {{-- Back link --}}
    <a href="{{ route('admin.users.index') }}"
       class="inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-brand-600">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Back to Users
    </a>

    <form wire:submit="save" class="space-y-6">

        {{-- ─── Basic info ─────────────────────────────────── --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-sm font-bold text-slate-900">Basic Information</h2>

            <div class="space-y-4">
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Full Name <span class="text-rose-500">*</span></label>
                    <input type="text" wire:model="name"
                           placeholder="e.g. Sonam Wangchuk"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                    @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Mobile <span class="text-rose-500">*</span></label>
                        <input type="text" wire:model="mobile"
                               placeholder="17123456"
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                        @error('mobile') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        <p class="mt-1 text-[11px] text-slate-400">Bhutanese Mobile format: 8 digits</p>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Email (optional)</label>
                        <input type="email" wire:model="email"
                               placeholder="staff@example.com"
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                        @error('email') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Status</label>
                    <select wire:model="status"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                        <option value="active">Active</option>
                        <option value="suspended">Suspended</option>
                    </select>
                    @error('status') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- ─── Roles ──────────────────────────────────────── --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-1 text-sm font-bold text-slate-900">Roles <span class="text-rose-500">*</span></h2>
            <p class="mb-4 text-xs text-slate-500">Assign one or more roles. Permissions are derived from roles.</p>

            <div class="grid gap-2 sm:grid-cols-2">
                @foreach ($roles as $role)
                    <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 p-3 transition hover:border-brand-300 hover:bg-brand-50/50
                                  {{ in_array($role, $selectedRoles, true) ? 'border-brand-500 bg-brand-50' : '' }}">
                        <input type="checkbox"
                               wire:model.live="selectedRoles"
                               value="{{ $role }}"
                               class="mt-0.5 h-4 w-4 rounded border-slate-300 text-brand-500 focus:ring-brand-500" />
                        <div>
                            <div class="text-sm font-medium text-slate-800">{{ $role }}</div>
                            <div class="text-xs text-slate-500">
                                @switch($role)
                                    @case('Admin')
                                        Full access to everything
                                        @break
                                    @case('Manager')
                                        Orders, payments, menu, inventory, reports
                                        @break
                                    @case('Kitchen Staff')
                                        View orders, mark preparing/ready
                                        @break
                                    @case('Delivery Staff')
                                        View orders, mark delivered
                                        @break
                                @endswitch
                            </div>
                        </div>
                    </label>
                @endforeach
            </div>
            @error('selectedRoles') <p class="mt-2 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>

        {{-- ─── Password ───────────────────────────────────── --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-1 text-sm font-bold text-slate-900">
                {{ $user ? 'Change Password' : 'Set Password' }}
                @if ($user) <span class="text-xs font-normal text-slate-500">(leave blank to keep current)</span> @endif
            </h2>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">
                        Password @if (! $user)<span class="text-rose-500">*</span>@endif
                    </label>
                    <input type="password" wire:model="password"
                           placeholder="At least 8 characters"
                           autocomplete="new-password"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                    @error('password') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Confirm Password</label>
                    <input type="password" wire:model="password_confirmation"
                           placeholder="Re-enter password"
                           autocomplete="new-password"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                </div>
            </div>
        </div>

        {{-- ─── Actions ────────────────────────────────────── --}}
        <div class="flex items-center justify-end gap-3">
            <a href="{{ $user ? route('admin.users.show', $user) : route('admin.users.index') }}"
               class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                Cancel
            </a>
            <button type="submit"
                    wire:loading.attr="disabled"
                    class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-600 disabled:opacity-50">
                <svg wire:loading wire:target="save" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                <span wire:loading.remove wire:target="save">{{ $user ? 'Save changes' : 'Create account' }}</span>
                <span wire:loading wire:target="save">Saving…</span>
            </button>
        </div>
    </form>
</div>