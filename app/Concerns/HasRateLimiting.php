<?php

namespace App\Concerns;

use Illuminate\Support\Facades\RateLimiter;
use RuntimeException;

trait HasRateLimiting
{
    /**
     * Throw if the key has hit its limit; otherwise record a hit.
     *
     * @throws RuntimeException
     */
    protected function rateLimit(string $key, int $maxAttempts, int $decaySeconds): void
    {
        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);

            throw new RuntimeException(sprintf(
                'Too many attempts. Please try again in %d %s.',
                $seconds,
                $seconds === 1 ? 'second' : 'seconds',
            ));
        }

        RateLimiter::hit($key, $decaySeconds);
    }
}