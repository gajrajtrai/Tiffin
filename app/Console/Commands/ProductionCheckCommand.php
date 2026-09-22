<?php

namespace App\Console\Commands;

use App\Modules\Core\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class ProductionCheckCommand extends Command
{
    protected $signature = 'production:check {--strict : Treat warnings as failures}';
    protected $description = 'Verify the app is production-ready before going live';

    public function handle(): int
    {
        $pass = 0;
        $warn = 0;
        $fail = 0;

        $this->info('Checking production readiness…');
        $this->newLine();

        // ─── Environment ──────────────────────────────────────────
        $this->check($fail, $pass, 'Environment', app()->environment() === 'production', 'fail',
            'APP_ENV is "'.app()->environment().'" (should be "production")');

        $this->check($fail, $pass, 'Debug Mode', ! config('app.debug'), 'fail',
            'APP_DEBUG is enabled — stack traces will leak');

        $this->check($fail, $pass, 'App Key', ! empty(config('app.key')), 'fail',
            'APP_KEY is not set');

        $this->check($fail, $pass, 'App URL', config('app.url') && config('app.url') !== 'http://localhost', 'fail',
            'APP_URL is "'.config('app.url').'" — set to your real server address');

        // ─── Performance ──────────────────────────────────────────
        $this->check($warn, $pass, 'Config Cache', app()->configurationIsCached(), 'warn',
            'Config is not cached — run php artisan config:cache');

        $this->check($warn, $pass, 'Route Cache', app()->routesAreCached(), 'warn',
            'Routes are not cached — run php artisan route:cache');

        $this->check($warn, $pass, 'View Cache', $this->viewsAreCached(), 'warn',
            'Views are not cached — run php artisan view:cache');

        // ─── Database ─────────────────────────────────────────────
        $pending = 0;
        try {
            $pending = count(app('migrator')->getMigrationFiles(app('migrator')->paths()));
        } catch (\Throwable $e) {
            // ignore
        }

        $this->check($fail, $pass, 'Migrations', $pending >= 0, 'fail',
            'Could not check migrations');

        // ─── Storage ──────────────────────────────────────────────
        $writable = is_writable(storage_path()) && is_writable(base_path('bootstrap/cache'));
        $this->check($fail, $pass, 'Storage Writable', $writable, 'fail',
            'storage/ or bootstrap/cache is not writable');

        $symlinkOk = file_exists(public_path('storage'));
        $this->check($warn, $pass, 'Storage Symlink', $symlinkOk, 'warn',
            'public/storage symlink missing — run php artisan storage:link');

        // ─── Security ─────────────────────────────────────────────
        $this->check($warn, $pass, 'Secure Cookies', (bool) config('session.secure'), 'warn',
            'SESSION_SECURE_COOKIE is false — cookies sent over HTTP');

        $this->check($warn, $pass, 'HTTP-only Cookies', (bool) config('session.http_only'), 'warn',
            'SESSION_HTTP_ONLY is false');

        $this->check($warn, $pass, 'Log Level', config('logging.channels.stack.level') !== 'debug', 'warn',
            'LOG_LEVEL is debug — excessive logging in production');

        // ─── Data ─────────────────────────────────────────────────
        try {
            $hasAdmin = \App\Models\User::whereHas('roles', fn ($q) => $q->where('name', 'Admin'))->exists();
            $this->check($fail, $pass, 'Admin User', $hasAdmin, 'fail',
                'No Admin user found — you cannot manage the system');
        } catch (\Throwable $e) {
            $this->check($fail, $pass, 'Admin User', false, 'fail',
                'Could not query users table — run migrations?');
        }

        try {
            $hasSettings = Schema::hasTable('settings') && Setting::get('restaurant_name');
            $this->check($fail, $pass, 'Settings Seeded', $hasSettings, 'fail',
                'Settings table is empty — run php artisan db:seed --class=SettingsSeeder');
        } catch (\Throwable $e) {
            $this->check($fail, $pass, 'Settings Seeded', false, 'fail',
                'Could not check settings table');
        }

        // ─── Result ───────────────────────────────────────────────
        $this->newLine();
        $this->line(sprintf(
            '<fg=green>%d passed</>  <fg=yellow>%d warnings</>  <fg=red>%d failed</>',
            $pass,
            $warn,
            $fail,
        ));
        $this->newLine();

        $ready = $fail === 0 && (! $this->option('strict') || $warn === 0);

        if ($ready) {
            $this->info('✓ Ready for deployment.');
            return self::SUCCESS;
        }

        $this->error('✗ Not ready for deployment.');
        return self::FAILURE;
    }

    /**
     * Dispatch to fail/warn counters based on severity.
     */
    protected function check(int &$failOrWarnCounter, int &$passCounter, string $label, bool $ok, string $severity, string $failureMessage): void
    {
        if ($ok) {
            $this->line(" <fg=green>PASS</>  {$label}");
            $passCounter++;
            return;
        }

        $color = $severity === 'fail' ? 'red' : 'yellow';
        $tag = $severity === 'fail' ? 'FAIL' : 'WARN';
        $this->line(" <fg={$color}>{$tag}</>  {$label}  <fg=gray>→ {$failureMessage}</>");
        $failOrWarnCounter++;
    }

    protected function viewsAreCached(): bool
    {
        // Compiled views exist if storage/framework/views has files
        $files = glob(storage_path('framework/views/*.php'));
        return ! empty($files);
    }
}