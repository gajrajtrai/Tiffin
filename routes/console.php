<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Scheduled Tasks
|--------------------------------------------------------------------------
|
| These run automatically via Windows Task Scheduler calling
| "php artisan schedule:run" every minute.
|
*/

/*
 * ─── Backup health watchdog ──────────────────────────────────────
 * Runs at 6:00 AM daily. If the last backup is older than 36 hours,
 * it writes an urgent entry to storage/logs/backup-health.log so
 * you'll see it in the admin dashboard / log review.
 */
Schedule::call(function () {
    $disk = \Illuminate\Support\Facades\Storage::disk('local');

    $files = $disk->files('Laravel');
    if (empty($files)) {
        $files = $disk->allFiles();
    }

    $zips = collect($files)->filter(fn ($f) => str_ends_with($f, '.zip'));

    if ($zips->isEmpty()) {
        \Illuminate\Support\Facades\Log::channel('single')->warning(
            '[BACKUP HEALTH] No backup files found at all — check Task Scheduler.'
        );
        return;
    }

    $latest = $zips
        ->map(fn ($f) => $disk->lastModified($f))
        ->max();

    $hoursOld = (time() - $latest) / 3600;

    if ($hoursOld > 36) {
        \Illuminate\Support\Facades\Log::channel('single')->warning(sprintf(
            '[BACKUP HEALTH] Latest backup is %.1f hours old — check Task Scheduler.',
            $hoursOld
        ));
    }
})
    ->name('backup-health-watchdog')
    ->dailyAt('06:00')
    ->timezone('Asia/Thimphu');

/*
 * ─── Prune audit log entries older than 90 days ──────────────────
 * Runs on the 1st of every month at 3:00 AM. Keeps the activity_log
 * table from growing forever.
 */
Schedule::call(function () {
    $cutoff = now()->subDays(90);

    $deleted = \Spatie\Activitylog\Models\Activity::query()
        ->where('created_at', '<', $cutoff)
        ->delete();

    \Illuminate\Support\Facades\Log::info(sprintf(
        '[AUDIT PRUNE] Deleted %d activity log entries older than %s.',
        $deleted,
        $cutoff->toDateString()
    ));
})
    ->name('audit-log-prune')
    ->monthlyOn(1, '03:00')
    ->timezone('Asia/Thimphu');

/*
 * ─── Prune stale soft-deleted records ────────────────────────────
 * Any soft-deleted MenuItem, InventoryItem, or Supplier older than
 * 90 days gets permanently removed. Logs a summary.
 */
Schedule::call(function () {
    $cutoff = now()->subDays(90);

    $counts = [
        'menu_items'      => \App\Modules\Menu\Models\MenuItem::onlyTrashed()
                                ->where('deleted_at', '<', $cutoff)->forceDelete(),
        'inventory_items' => \App\Modules\Inventory\Models\InventoryItem::onlyTrashed()
                                ->where('deleted_at', '<', $cutoff)->forceDelete(),
        'suppliers'       => \App\Modules\Supplier\Models\Supplier::onlyTrashed()
                                ->where('deleted_at', '<', $cutoff)->forceDelete(),
    ];

    \Illuminate\Support\Facades\Log::info(sprintf(
        '[SOFT-DELETE PRUNE] menu_items=%d inventory=%d suppliers=%d',
        $counts['menu_items'],
        $counts['inventory_items'],
        $counts['suppliers'],
    ));
})
    ->name('soft-delete-prune')
    ->weeklyOn(0, '03:30')  // Sundays at 3:30 AM
    ->timezone('Asia/Thimphu');

/*
 * ─── Prune stale sessions ────────────────────────────────────────
 * Removes session rows older than 8 hours. Light DB hygiene.
 */
Schedule::call(function () {
    if (config('session.driver') !== 'database') {
        return;
    }

    $cutoff = now()->subHours(8)->getTimestamp();

    $deleted = \Illuminate\Support\Facades\DB::table('sessions')
        ->where('last_activity', '<', $cutoff)
        ->delete();

    if ($deleted > 0) {
        \Illuminate\Support\Facades\Log::info('[SESSION PRUNE] Removed '.$deleted.' stale sessions.');
    }
})
    ->name('session-prune')
    ->dailyAt('04:00')
    ->timezone('Asia/Thimphu');
/*
 * ─── Auto-cancel untouched orders ────────────────────────────────
 * Runs at 22:00 daily. Any order from today still in "pending"
 * status (never forwarded to kitchen) is cancelled with a full
 * wallet refund. PREPARING and READY orders are left for manual
 * handling — the kitchen already engaged with them.
 */
Schedule::call(function () {
    $service = app(\App\Modules\Order\Services\OrderService::class);

    // No admin user to attribute the action to — pass null; the service
    // uses auth()->user() when available, but for scheduled tasks it
    // can accept null and skip the causer.
    $systemUser = \App\Models\User::whereHas('roles', fn ($q) => $q->where('name', 'Admin'))
        ->first();

    if (! $systemUser) {
        \Illuminate\Support\Facades\Log::warning('[AUTO-CANCEL] No admin user found — skipping.');
        return;
    }

    $orders = \App\Modules\Order\Models\Order::query()
        ->whereDate('service_date', today())
        ->whereIn('status', [
            \App\Modules\Order\Models\Order::STATUS_PENDING,
            \App\Modules\Order\Models\Order::STATUS_CONFIRMED,
        ])
        ->get();

    if ($orders->isEmpty()) {
        return;
    }

    $cancelled = 0;
    $failed = 0;

    foreach ($orders as $order) {
        try {
            $service->cancel(
                order:      $order,
                performedBy: $systemUser,
                reason:     'Order not processed in time — auto-cancelled',
            );
            $cancelled++;
        } catch (\Throwable $e) {
            $failed++;
        }
    }

    \Illuminate\Support\Facades\Log::info(sprintf(
        '[AUTO-CANCEL] %d order(s) auto-cancelled and refunded; %d failed.',
        $cancelled,
        $failed,
    ));
})
    ->name('auto-cancel-unprocessed-orders')
    ->dailyAt('22:00')
    ->timezone('Asia/Thimphu');