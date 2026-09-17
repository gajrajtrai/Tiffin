<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-cream-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="flex min-h-full flex-col font-sans antialiased text-slate-800">

    <x-public.top-nav />

    <main class="flex-1 pb-24 sm:pb-8">
        {{ $slot }}
    </main>

    <x-public.bottom-nav />

    <footer class="hidden border-t border-slate-200 bg-white sm:block">
        <div class="mx-auto max-w-5xl px-4 py-6 text-center text-xs text-slate-500">
            {{ config('app.name') }} · Near College Gate, Thimphu · +975 17 123 456
        </div>
    </footer>

    @livewireScripts
    @stack('scripts')
</body>
</html>