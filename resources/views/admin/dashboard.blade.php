<x-layouts.admin title="Dashboard" heading="Dashboard">
    <div class="space-y-6">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-admin.stat-card label="Today's Orders"   value="0"  icon="receipt"     color="brand"   trend="—" trendUp="true" />
            <x-admin.stat-card label="Revenue (Paid)"   value="Nu. 0" icon="banknotes" color="emerald" trend="—" trendUp="true" />
            <x-admin.stat-card label="Pending Prep"     value="0"  icon="cube"        color="sky"     />
            <x-admin.stat-card label="Items Available"  value="0"  icon="book"        color="rose"    />
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-bold text-slate-900">Welcome to {{ config('app.name') }} Admin</h2>
            <p class="mt-1 text-sm text-slate-600">Placeholder dashboard. Real data wired in Phase 6.</p>
            <div class="mt-4 flex flex-wrap gap-2">
                <x-admin.badge variant="success">Phase 3 Layout OK</x-admin.badge>
                <x-admin.badge variant="info">Tailwind v4</x-admin.badge>
                <x-admin.badge variant="brand">Livewire 3</x-admin.badge>
            </div>
        </div>
        <div class="space-y-3">
            <x-admin.alert type="success">All systems nominal. Role and permission matrix verified.</x-admin.alert>
            <x-admin.alert type="info">Delivery hours today: 11:00 AM – 2:00 PM. Order cut-off at 11:00 AM.</x-admin.alert>
        </div>
    </div>
</x-layouts.admin>