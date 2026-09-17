@props(['variant' => 'slate'])
@php
    $variants = [
        'slate'   => 'bg-slate-100 text-slate-700 ring-slate-200',
        'brand'   => 'bg-brand-100 text-brand-700 ring-brand-200',
        'success' => 'bg-emerald-100 text-emerald-700 ring-emerald-200',
        'warning' => 'bg-amber-100 text-amber-800 ring-amber-200',
        'danger'  => 'bg-rose-100 text-rose-700 ring-rose-200',
        'info'    => 'bg-sky-100 text-sky-700 ring-sky-200',
    ];
@endphp
<span {{ $attributes->merge([
    'class' => 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset '.($variants[$variant] ?? $variants['slate'])
]) }}>
    {{ $slot }}
</span>