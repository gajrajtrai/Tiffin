<div class="space-y-6">

    {{-- ─── Pending closure requests banner ────────────────── --}}
    @php
        $pendingClosures = \App\Models\AccountClosureRequest::pending()->count();
    @endphp

    @if ($pendingClosures > 0 && auth()->user()->can('user.edit'))
        <a href="{{ route('admin.users.closure-requests') }}"
           class="flex items-center gap-3 rounded-xl border border-rose-200 bg-rose-50 p-4 transition hover:border-rose-300">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-rose-100 text-rose-700">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <div class="text-sm font-bold text-rose-900">
                    {{ $pendingClosures }} account closure {{ $pendingClosures === 1 ? 'request' : 'requests' }} pending
                </div>
                <div class="text-xs text-rose-700">Click to review</div>
            </div>
            <svg class="h-4 w-4 shrink-0 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
    @endif

    {{-- ─── Page header ────────────────────────────────────── --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-900">User Management</h1>
            <p class="mt-1 text-sm text-slate-500">Manage customers, staff, roles, and access.</p>
        </div>
        @can('user.create')
            <a href="{{ route('admin.users.create') }}"
               class="inline-flex items-center gap-2 self-start rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-600 sm:self-auto">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add Staff
            </a>
        @endcan
    </div>
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
                        <a href="{{ route('admin.users.show', $user) }}"
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