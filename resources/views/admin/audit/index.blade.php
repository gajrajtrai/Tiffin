<div class="space-y-6">

    {{-- ─── Header ─────────────────────────────────────────── --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Audit Log</h1>
            <p class="mt-1 text-sm text-slate-500">
                Every recorded change across the system — who did what, and when.
            </p>
        </div>
        <button type="button" wire:click="pruneOldLogs"
                wire:confirm="Delete all log entries older than 90 days? This cannot be undone."
                class="inline-flex items-center gap-2 self-start rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-rose-600 hover:bg-rose-50 hover:border-rose-300 sm:self-auto">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
            Prune 90+ days
        </button>
    </div>

    @if ($statusMessage)
        <x-admin.alert :type="$statusType">{{ $statusMessage }}</x-admin.alert>
    @endif

    {{-- ─── Stats ──────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <x-admin.stat-card label="Total Events" :value="$stats['total']" icon="shield" color="brand" />
        <x-admin.stat-card label="Created" :value="$stats['creates']" icon="shield" color="emerald" />
        <x-admin.stat-card label="Updated" :value="$stats['updates']" icon="shield" color="sky" />
        <x-admin.stat-card label="Deleted" :value="$stats['deletes']" icon="shield" color="rose" />
    </div>

    {{-- ─── Filters ────────────────────────────────────────── --}}
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid gap-3 sm:grid-cols-12">

            <div class="sm:col-span-3">
                <label class="mb-1 block text-xs font-medium text-slate-600">Model</label>
                <select wire:model.live="logNameFilter"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                    <option value="">All models</option>
                    @foreach ($logNames as $name)
                        <option value="{{ $name }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="sm:col-span-2">
                <label class="mb-1 block text-xs font-medium text-slate-600">Event</label>
                <select wire:model.live="eventFilter"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                    <option value="">All events</option>
                    @foreach ($events as $ev)
                        <option value="{{ $ev }}">{{ ucfirst($ev) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="sm:col-span-3">
                <label class="mb-1 block text-xs font-medium text-slate-600">User</label>
                <select wire:model.live="userFilter"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                    <option value="">All users</option>
                    @foreach ($causers as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="sm:col-span-2">
                <label class="mb-1 block text-xs font-medium text-slate-600">From</label>
                <input type="date" wire:model.live="from"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
            </div>

            <div class="sm:col-span-2">
                <label class="mb-1 block text-xs font-medium text-slate-600">To</label>
                <input type="date" wire:model.live="to"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
            </div>

            <div class="sm:col-span-9">
                <label class="mb-1 block text-xs font-medium text-slate-600">Search description</label>
                <input type="text" wire:model.live.debounce.300ms="search"
                       placeholder="e.g. TTF-20260921, Veg Thali, price"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
            </div>

            <div class="flex items-end gap-2 sm:col-span-3">
                <button type="button" wire:click="presetToday"
                        class="flex-1 rounded-md border border-slate-300 bg-white px-2 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50">
                    Today
                </button>
                <button type="button" wire:click="presetLast7"
                        class="flex-1 rounded-md border border-slate-300 bg-white px-2 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50">
                    7d
                </button>
                <button type="button" wire:click="presetLast30"
                        class="flex-1 rounded-md border border-slate-300 bg-white px-2 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50">
                    30d
                </button>
                <button type="button" wire:click="clearFilters"
                        class="rounded-md border border-slate-300 bg-white px-2 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50"
                        title="Clear filters">
                    ✕
                </button>
            </div>
        </div>
    </div>

    {{-- ─── Timeline ───────────────────────────────────────── --}}
    @if ($activities->isEmpty())
        <div class="rounded-xl border border-slate-200 bg-white p-12 text-center">
            <p class="text-sm text-slate-500">No activity in this range.</p>
        </div>
    @else
        <div class="space-y-2">
            @foreach ($activities as $activity)
                @php
                    $eventIcon = match ($activity->event) {
                        'created' => ['+', 'bg-emerald-100 text-emerald-700'],
                        'updated' => ['✎', 'bg-amber-100 text-amber-700'],
                        'deleted' => ['−', 'bg-rose-100 text-rose-700'],
                        default   => ['•', 'bg-slate-100 text-slate-600'],
                    };
                    [$iconChar, $iconClass] = $eventIcon;

                    $causerName = $activity->causer?->name ?? 'System';
                    $modelLabel = $activity->log_name ?? class_basename($activity->subject_type ?? 'Unknown');

                    $changes = $activity->properties['attributes'] ?? [];
                    $old = $activity->properties['old'] ?? [];
                    $isUpdate = $activity->event === 'updated' && ! empty($old);
                    $isExpanded = $expandedId === $activity->id;
                @endphp

                <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
                     wire:key="activity-{{ $activity->id }}">
                    <button type="button" wire:click="toggleExpand({{ $activity->id }})"
                            class="flex w-full items-start gap-4 p-4 text-left hover:bg-slate-50">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-sm font-bold {{ $iconClass }}">
                            {{ $iconChar }}
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-sm font-semibold text-slate-900">{{ $causerName }}</span>
                                <span class="text-xs text-slate-400">{{ $activity->event }}</span>
                                @if ($modelLabel)
                                    <span class="inline-flex rounded bg-brand-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-brand-700">
                                        {{ $modelLabel }}
                                    </span>
                                @endif
                                @if ($activity->description)
                                    <span class="text-sm text-slate-700">{{ $activity->description }}</span>
                                @endif
                            </div>
                            <div class="mt-1 text-xs text-slate-400">
                                {{ $activity->created_at->format('M j, Y · H:i:s') }}
                                @if ($isUpdate)
                                    · {{ count($old) }} {{ count($old) === 1 ? 'field' : 'fields' }} changed
                                @elseif (! empty($changes))
                                    · {{ count($changes) }} {{ count($changes) === 1 ? 'field' : 'fields' }}
                                @endif
                            </div>
                        </div>

                        <div class="shrink-0 text-slate-400">
                            <svg class="h-4 w-4 transition-transform {{ $isExpanded ? 'rotate-180' : '' }}"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </div>
                    </button>

                    {{-- Expanded details --}}
                    @if ($isExpanded)
                        <div class="border-t border-slate-100 bg-slate-50 p-4">
                            @if ($isUpdate)
                                <div class="overflow-hidden rounded-lg border border-slate-200 bg-white text-xs">
                                    <div class="grid grid-cols-3 gap-3 border-b border-slate-200 bg-slate-50 px-3 py-2 font-semibold text-slate-500">
                                        <div>Field</div>
                                        <div>Before</div>
                                        <div>After</div>
                                    </div>
                                    @foreach ($old as $field => $oldValue)
                                        @php
                                            $newValue = $changes[$field] ?? '(removed)';
                                            $format = fn ($v) => is_bool($v) ? ($v ? 'true' : 'false')
                                                : (is_array($v) ? json_encode($v) : (string) $v);
                                        @endphp
                                        <div class="grid grid-cols-3 gap-3 border-t border-slate-100 px-3 py-2">
                                            <div class="font-mono text-slate-700">{{ $field }}</div>
                                            <div class="text-rose-600 break-all">{{ \Illuminate\Support\Str::limit($format($oldValue), 200) }}</div>
                                            <div class="text-emerald-600 break-all">{{ \Illuminate\Support\Str::limit($format($newValue), 200) }}</div>
                                        </div>
                                    @endforeach
                                </div>
                            @elseif (! empty($changes))
                                <div class="overflow-hidden rounded-lg border border-slate-200 bg-white text-xs">
                                    <div class="border-b border-slate-200 bg-slate-50 px-3 py-2 font-semibold text-slate-500">
                                        Attributes
                                    </div>
                                    @foreach ($changes as $field => $value)
                                        @php
                                            $format = fn ($v) => is_bool($v) ? ($v ? 'true' : 'false')
                                                : (is_array($v) ? json_encode($v) : (string) $v);
                                        @endphp
                                        <div class="grid grid-cols-3 gap-3 border-t border-slate-100 px-3 py-2">
                                            <div class="font-mono text-slate-700">{{ $field }}</div>
                                            <div class="col-span-2 text-slate-800 break-all">{{ \Illuminate\Support\Str::limit($format($value), 300) }}</div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-xs text-slate-500">No additional details recorded.</p>
                            @endif

                            <div class="mt-3 flex flex-wrap items-center gap-3 text-[11px] text-slate-400">
                                @if ($activity->subject_type)
                                    <span>
                                        Subject: <span class="font-mono">{{ class_basename($activity->subject_type) }}#{{ $activity->subject_id }}</span>
                                    </span>
                                @endif
                                @if ($activity->causer_type)
                                    <span>
                                        Causer: <span class="font-mono">{{ class_basename($activity->causer_type) }}#{{ $activity->causer_id }}</span>
                                    </span>
                                @endif
                                <span>Log: <span class="font-mono">{{ $activity->log_name }}</span></span>
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <div>{{ $activities->links() }}</div>
    @endif
</div>