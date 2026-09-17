@props([
    'name'   => 'modal',
    'title'  => '',
    'maxWidth' => 'md',   // sm | md | lg | xl
])

@php
    $widths = [
        'sm' => 'max-w-sm',
        'md' => 'max-w-md',
        'lg' => 'max-w-lg',
        'xl' => 'max-w-2xl',
    ];
    $widthClass = $widths[$maxWidth] ?? $widths['md'];
@endphp

<div x-data="{ open: false }"
     x-on:open-modal-{{ $name }}.window="open = true"
     x-on:close-modal-{{ $name }}.window="open = false"
     x-on:keydown.escape.window="open = false"
     x-show="open"
     x-cloak
     class="fixed inset-0 z-50 overflow-y-auto"
     style="display: none;">

    {{-- Backdrop --}}
    <div x-show="open"
         x-transition.opacity
         @click="open = false"
         class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"></div>

    {{-- Modal panel --}}
    <div class="relative flex min-h-full items-center justify-center p-4">
        <div x-show="open"
             x-transition
             @click.outside="open = false"
             class="w-full {{ $widthClass }} rounded-xl bg-white shadow-2xl">

            @if ($title)
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-3">
                    <h3 class="text-sm font-semibold text-slate-900">{{ $title }}</h3>
                    <button type="button" @click="open = false"
                            class="rounded-md p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            @endif

            <div class="px-5 py-4">
                {{ $slot }}
            </div>

            @isset($footer)
                <div class="flex justify-end gap-2 border-t border-slate-200 bg-slate-50 px-5 py-3">
                    {{ $footer }}
                </div>
            @endisset
        </div>
    </div>
</div>