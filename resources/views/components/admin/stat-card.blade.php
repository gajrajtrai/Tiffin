@props([
    'label'   => 'Label',
    'value'   => '0',
    'icon'    => 'chart-bar',
    'trend'   => null,
    'trendUp' => true,
    'color'   => 'brand',
])
@php
    $colors = [
        'brand'   => 'bg-brand-100 text-brand-600',
        'emerald' => 'bg-emerald-100 text-emerald-600',
        'sky'     => 'bg-sky-100 text-sky-600',
        'rose'    => 'bg-rose-100 text-rose-600',
    ];
@endphp
<div {{ $attributes->merge(['class' => 'rounded-xl border border-slate-200 bg-white p-5 shadow-sm']) }}>
    <div class="flex items-start justify-between">
        <div class="flex-1">
            <div class="text-xs font-medium uppercase tracking-wider text-slate-500">{{ $label }}</div>
            <div class="mt-2 text-2xl font-bold text-slate-900">{{ $value }}</div>
            @if ($trend)
                <div class="mt-1 inline-flex items-center gap-1 text-xs font-medium {{ $trendUp ? 'text-emerald-600' : 'text-rose-600' }}">
                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        @if ($trendUp)
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7"/>
                        @else
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                        @endif
                    </svg>
                    {{ $trend }}
                </div>
            @endif
        </div>
        <div class="flex h-11 w-11 items-center justify-center rounded-lg {{ $colors[$color] ?? $colors['brand'] }}">
            <x-admin.icon :name="$icon" class="h-5 w-5" />
        </div>
    </div>
</div>