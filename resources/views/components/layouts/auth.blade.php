@use('App\Modules\Core\Models\Setting')

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-cream-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Sign in' }} · {{ config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="flex min-h-full flex-col font-sans antialiased text-slate-800">

    <div class="flex min-h-full flex-col justify-center px-4 py-10 sm:py-16">
        <div class="mx-auto w-full max-w-sm">

            {{-- Brand --}}
            <div class="mb-8 text-center">
                <a href="{{ url('/') }}" class="inline-flex items-center gap-2">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-500 text-white shadow-sm">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                    </div>
                    <span class="text-lg font-bold text-slate-900">{{ config('app.name') }}</span>
                </a>
            </div>

            {{-- Card --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                {{ $slot }}
            </div>

            {{-- Small footer --}}
            <div class="mt-6 text-center text-xs text-slate-500">
                @php
                    $footerMobile = Setting::get('restaurant_mobile', '');
                @endphp
                {{ Setting::get('restaurant_name', config('app.name')) }}
                @if ($footerMobile)
                    · {{ $footerMobile }}
                @endif
            </div>

            {{-- Back to home --}}
            <div class="mt-4 text-center">
                <a href="{{ url('/') }}"
                   class="inline-flex items-center gap-1 text-xs font-medium text-slate-500 hover:text-brand-600">
                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Back to home
                </a>
            </div>
        </div>
    </div>

    @livewireScripts
    @stack('scripts')
</body>
</html>