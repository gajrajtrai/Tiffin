<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Fortify\Features;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Safety net: refuse to run tests against the real database.
        // This catches the config-cache-leak scenario that wiped the
        // `tiffin` database earlier — RefreshDatabase with APP_ENV=local
        // silently targets whatever DB .env points at.
        $db = config('database.connections.'.config('database.default').'.database');

        if (! str_ends_with((string) $db, '_test')) {
            $this->fail(
                "Refusing to run tests against non-test database [{$db}]. "
                .'Test database must end with _test. Check phpunit.xml and run '
                .'`php artisan optimize:clear` if this still fires.'
            );
        }

        // Additional guard: tests must never run under APP_ENV=local,
        // because VerifyCsrfToken skips CSRF checks only in `testing`.
        if (app()->environment('local')) {
            $this->fail(
                'Tests must run in the [testing] environment, got [local]. '
                .'Run `php artisan optimize:clear` before `php artisan test`.'
            );
        }
    }

    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }
}