<div class="space-y-6">

    {{-- ─── Summary cards ──────────────────────────────────── --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <x-admin.stat-card label="Total Users" :value="$counts['total']" icon="users" color="brand" />
        <x-admin.stat-card label="Customers" :value="$counts['customers']" icon="users" color="sky" />
        <x-admin.stat-card label="Staff" :value="$counts['staff']" icon="users" color="emerald" />
        <x-admin.stat-card label="Suspended" :value="$counts['suspended']" icon="users" color="rose" />
    </div>

    {{-- ─── Filters ────────────────────────────────────────── --}}
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid gap-3 sm:grid-cols-12">

            {{-- Search --}}
            <div class="sm:col-span-5">
                <label class="mb-1 block text-xs font-medium text-slate-600">Search</label>
                <div class="relative">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/>
                    </svg>
                    <input type="text"
                           wire:model.live.debounce.300ms="search"
                           placeholder="Name, mobile, or email…"
                           class="w-full rounded-lg border border-slate-300 py-2 pl-9 pr-3 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                </div>
            </div>

            {{-- Role filter --}}
            <div class="sm:col-span-3">
                <label class="mb-1 block text-xs font-medium text-slate-600">Role</label>
                <select wire:model.live="roleFilter"
                        class="w-full rounded-lg border border-slate-300 py-2 px-3 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                    <option value="">All roles</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role }}">{{ $role }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Status filter --}}
            <div class="sm:col-span-2">
                <label class="mb-1 block text-xs font-medium text-slate-600">Status</label>
                <select wire:model.live="statusFilter"
                        class="w-full rounded-lg border border-slate-300 py-2 px-3 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                    <option value="all">All</option>
                    <option value="active">Active</option>
                    <option value="suspended">Suspended</option>
                </select>
            </div>

            {{-- Clear --}}
            <div class="flex items-end sm:col-span-2">
                <button type="button"
                        wire:click="clearFilters"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    Clear
                </button>
            </div>
        </div>
    </div>

    {{-- ─── Users table ────────────────────────────────────── --}}
    @if ($users->isEmpty())
        <div class="rounded-xl border border-slate-200 bg-white p-12 text-center">
            <p class="text-sm text-slate-500">No users match your filters.</p>
        </div>
    @else
        <x-admin.data-table :headers="['User', 'Contact', 'Roles', 'Wallet', 'Status', '']">
            @foreach ($users as $user)
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-500 text-xs font-bold text-white">
                                {{ $user->initials() }}
                            </div>
                            <div class="min-w-0">
                                <div class="truncate font-medium text-slate-900">{{ $user->name }}</div>
                                <div class="text-xs text-slate-400">
                                    #{{ $user->id }} · joined {{ $user->created_at->format('M j, Y') }}
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        @if ($user->mobile)
                            <div class="text-sm text-slate-700">{{ $user->mobile }}</div>
                        @endif
                        @if ($user->email)
                            <div class="text-xs text-slate-500">{{ $user->email }}</div>
                        @endif
                        @if (! $user->mobile && ! $user->email)
                            <span class="text-xs text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @forelse ($user->roles as $role)
                            <x-admin.badge
                                :variant="match($role->name) {
                                    'Admin' => 'danger',
                                    'Manager' => 'brand',
                                    'Kitchen Staff' => 'info',
                                    'Delivery Staff' => 'warning',
                                    'Customer' => 'slate',
                                    default => 'slate',
                                }">
                                {{ $role->name }}
                            </x-admin.badge>
                        @empty
                            <span class="text-xs text-slate-400">No role</span>
                        @endforelse
                    </td>
                    <td class="px-4 py-3">
                        @if ($user->hasRole('Customer'))
                            <div class="font-semibold text-slate-900">
                                Nu. {{ number_format($user->wallet_balance, 2) }}
                            </div>
                            @if ($user->hasLowBalance())
                                <div class="text-[10px] font-medium uppercase text-rose-600">low balance</div>
                            @endif
                        @else
                            <span class="text-xs text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <x-admin.badge :variant="$user->status === 'active' ? 'success' : 'danger'">
                            {{ ucfirst($user->status) }}
                        </x-admin.badge>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <a href="#"
                           class="inline-flex items-center gap-1 text-sm font-medium text-brand-600 hover:text-brand-700">
                            View
                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </td>
                </tr>
            @endforeach
        </x-admin.data-table>

        {{-- Pagination --}}
        <div>
            {{ $users->links() }}
        </div>
    @endif
</div>