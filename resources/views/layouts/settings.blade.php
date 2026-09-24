<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-cream-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Settings' }} · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="flex min-h-full flex-col font-sans antialiased text-slate-800">

    <x-public.top-nav />

    <main class="flex-1 pb-24 sm:pb-8">
        <div class="mx-auto max-w-5xl px-4 py-6 sm:py-10">

            <div class="mb-6">
                <h1 class="text-2xl font-bold text-slate-900">Account Settings</h1>
                <p class="mt-1 text-sm text-slate-500">Manage your profile and password.</p>
            </div>

            {{ $slot }}
        </div>
    </main>

    <x-public.bottom-nav />

    <footer class="hidden border-t border-slate-200 bg-white sm:block">
        <div class="mx-auto max-w-5xl px-4 py-6 text-center text-xs text-slate-500">
            @use('App\Modules\Core\Models\Setting')
            {{ Setting::get('restaurant_name', config('app.name')) }}
            @if (Setting::get('restaurant_mobile'))
                · {{ Setting::get('restaurant_mobile') }}
            @endif
        </div>
    </footer>

    @livewireScripts
    @stack('scripts')
</body>
</html>