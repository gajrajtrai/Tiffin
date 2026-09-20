@php
    $user = auth()->user();

    $items = [
        ['label' => 'Home',    'url' => url('/'),                'icon' => 'home',    'active' => request()->is('/')],
        ['label' => 'Menu',    'url' => route('menu.index'),     'icon' => 'book',    'active' => request()->is('menu*')],
        ['label' => 'Orders',  'url' => url('/orders'),          'icon' => 'receipt', 'active' => request()->is('orders*')],
        ['label' => 'Wallet',  'url' => route('wallet.index'),   'icon' => 'banknotes', 'active' => request()->is('wallet*')],
        ['label' => 'Profile', 'url' => url('/settings/profile'), 'icon' => 'users',   'active' => request()->is('settings*')],
    ];
@endphp

<nav class="fixed inset-x-0 bottom-0 z-40 border-t border-slate-200 bg-white/95 backdrop-blur sm:hidden">
    <div class="grid grid-cols-5">
        @foreach ($items as $item)
            <a href="{{ $item['url'] }}"
               @class([
                   'flex flex-col items-center gap-0.5 py-2 text-[10px] font-medium transition',
                   'text-brand-600' => $item['active'],
                   'text-slate-500 hover:text-brand-500' => ! $item['active'],
               ])>
                <x-admin.icon :name="$item['icon']" class="h-5 w-5" />
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach
    </div>
</nav>