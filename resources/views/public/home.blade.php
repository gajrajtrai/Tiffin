<x-layouts.public title="{{ config('app.name') }} · Fresh Tiffins, Delivered">
    {{-- Hero --}}
    <section class="bg-gradient-to-b from-brand-50 to-cream-50">
        <div class="mx-auto max-w-5xl px-4 py-10 sm:py-16">
            <span class="inline-flex items-center gap-1 rounded-full bg-brand-100 px-3 py-1 text-xs font-medium text-brand-700">
                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Order cut-off today: 11:00 AM
            </span>

            <h1 class="mt-4 text-3xl font-bold leading-tight text-slate-900 sm:text-5xl">
                Fresh lunch from <span class="text-brand-500">Tamkulay Tiffins</span>
            </h1>
            <p class="mt-3 max-w-xl text-base text-slate-600 sm:text-lg">
                Home-style meals delivered to your college gate or ready for pickup at our counter.
                Prepaid wallet, no queues, no fuss.
            </p>

            <div class="mt-6 flex flex-wrap gap-3">
                <a href="{{ url('/menu') }}"
                   class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-brand-600">
                    View today's menu
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
                <a href="{{ route('register') }}"
                   class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:border-brand-300 hover:text-brand-600">
                    Create account
                </a>
            </div>
        </div>
    </section>

    {{-- How it works --}}
    <section class="mx-auto max-w-5xl px-4 py-10">
        <h2 class="text-lg font-bold text-slate-900 sm:text-xl">How it works</h2>
        <div class="mt-5 grid gap-4 sm:grid-cols-3">

            @php
                $steps = [
                    ['icon' => 'users', 'title' => '1. Register', 'text' => 'Sign up with your mobile number in under a minute.'],
                    ['icon' => 'banknotes', 'title' => '2. Top up wallet', 'text' => 'Scan our QR, pay, upload the screenshot — we credit your wallet.'],
                    ['icon' => 'receipt', 'title' => '3. Order & enjoy', 'text' => 'Pick your meal for the day. Delivered to the gate or pickup at counter.'],
                ];
            @endphp

            @foreach ($steps as $step)
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-brand-100 text-brand-600">
                        <x-admin.icon :name="$step['icon']" class="h-5 w-5" />
                    </div>
                    <h3 class="mt-3 text-sm font-bold text-slate-900">{{ $step['title'] }}</h3>
                    <p class="mt-1 text-sm text-slate-600">{{ $step['text'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Info strip --}}
    <section class="mx-auto max-w-5xl px-4 pb-10">
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-5">
                <h3 class="flex items-center gap-2 text-sm font-bold text-emerald-800">
                    <x-admin.icon name="truck" class="h-4 w-4" />
                    Delivery
                </h3>
                <p class="mt-1 text-sm text-emerald-700">
                    Lunch only · 11:00 AM – 2:00 PM · Delivered to college gate.
                </p>
            </div>
            <div class="rounded-xl border border-sky-200 bg-sky-50 p-5">
                <h3 class="flex items-center gap-2 text-sm font-bold text-sky-800">
                    <x-admin.icon name="home" class="h-4 w-4" />
                    Pickup
                </h3>
                <p class="mt-1 text-sm text-sky-700">
                    Any time during opening hours · Our counter, near the college.
                </p>
            </div>
        </div>
    </section>
</x-layouts.public>