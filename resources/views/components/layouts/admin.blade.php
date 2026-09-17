<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-cream-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Dashboard' }} · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full font-sans antialiased text-slate-800">
    <div x-data="{ sidebarOpen: false }" class="min-h-full">
        <div x-show="sidebarOpen" x-transition.opacity @click="sidebarOpen = false"
             class="fixed inset-0 z-40 bg-slate-900/50 lg:hidden" style="display: none;"></div>
        <x-admin.sidebar />
        <div class="lg:pl-64">
            <header class="sticky top-0 z-30 flex h-16 items-center gap-4 border-b border-slate-200 bg-white/95 px-4 backdrop-blur lg:px-6">
                <button type="button" @click="sidebarOpen = true" class="lg:hidden -m-2 p-2 text-slate-600 hover:text-slate-900">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <div class="flex-1">
                    <h1 class="text-lg font-semibold text-slate-900">{{ $heading ?? ($title ?? 'Dashboard') }}</h1>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ url('/') }}" target="_blank"
                       class="hidden sm:inline-flex items-center gap-1 text-sm text-slate-500 hover:text-brand-600">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                        </svg>
                        View site
                    </a>
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open"
                                class="flex items-center gap-2 rounded-full bg-slate-100 py-1.5 pl-1.5 pr-3 text-sm font-medium text-slate-700 hover:bg-slate-200">
                            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-brand-500 text-xs font-bold text-white">
                                {{ auth()->user()?->initials() ?? '?' }}
                            </span>
                            <span class="hidden sm:inline">{{ auth()->user()?->name ?? 'Guest' }}</span>
                            <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div x-show="open" x-transition @click.outside="open = false"
                             class="absolute right-0 mt-2 w-56 rounded-lg border border-slate-200 bg-white py-1 shadow-lg"
                             style="display: none;">
                            <div class="border-b border-slate-100 px-4 py-2 text-xs text-slate-500">
                                Signed in as<br>
                                <span class="font-medium text-slate-700">{{ auth()->user()?->email }}</span>
                            </div>
                            <a href="{{ url('/settings/profile') }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Profile</a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50">Sign out</button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>
            <main class="p-4 lg:p-6">
                @if (session('status'))
                    <x-admin.alert type="success" class="mb-4">{{ session('status') }}</x-admin.alert>
                @endif
                @if (session('error'))
                    <x-admin.alert type="error" class="mb-4">{{ session('error') }}</x-admin.alert>
                @endif
                {{ $slot }}
            </main>
        </div>
    </div>
    @livewireScripts
    @stack('scripts')
</body>
</html>