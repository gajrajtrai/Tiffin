<div class="grid gap-6 lg:grid-cols-4">

    {{-- Left sidebar — tabs --}}
    <aside class="h-fit rounded-xl border border-slate-200 bg-white p-2 shadow-sm">
        <nav class="space-y-1">
            <a href="{{ route('profile.edit') }}" wire:navigate
               @class([
                   'flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition',
                   'bg-brand-500 text-white shadow-sm' => request()->routeIs('profile.*'),
                   'text-slate-600 hover:bg-slate-100' => ! request()->routeIs('profile.*'),
               ])>
                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                Profile
            </a>

            <a href="{{ route('security.edit') }}" wire:navigate
               @class([
                   'flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition',
                   'bg-brand-500 text-white shadow-sm' => request()->routeIs('security.*'),
                   'text-slate-600 hover:bg-slate-100' => ! request()->routeIs('security.*'),
               ])>
                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                Password
            </a>
        </nav>
    </aside>

    {{-- Right pane — content --}}
    <div class="lg:col-span-3">
        @if (! empty($heading))
            <div class="mb-5">
                <h2 class="text-lg font-bold text-slate-900">{{ $heading }}</h2>
                @if (! empty($subheading))
                    <p class="mt-1 text-sm text-slate-500">{{ $subheading }}</p>
                @endif
            </div>
        @endif

        {{ $slot }}
    </div>
</div>