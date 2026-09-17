@props([
    'title'     => '',
    'subtitle'  => null,
    'actionLabel' => null,
    'actionUrl'   => null,
    'actionIcon'  => 'book',
])

<div {{ $attributes->merge(['class' => 'mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between']) }}>
    <div>
        <h1 class="text-xl font-bold text-slate-900 sm:text-2xl">{{ $title }}</h1>
        @if ($subtitle)
            <p class="mt-1 text-sm text-slate-500">{{ $subtitle }}</p>
        @endif
    </div>

    @if ($actionLabel && $actionUrl)
        <a href="{{ $actionUrl }}"
           class="inline-flex items-center gap-2 self-start rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-600 sm:self-auto">
            <x-admin.icon :name="$actionIcon" class="h-4 w-4" />
            {{ $actionLabel }}
        </a>
    @endif

    {{ $slot ?? '' }}
</div>