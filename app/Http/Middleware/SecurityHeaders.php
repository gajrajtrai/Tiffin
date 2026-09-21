<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Add safe security headers to every web response.
     *
     * Note: Content-Security-Policy is intentionally NOT set here.
     * CSP requires careful tuning around Alpine.js, Livewire, and Vite
     * to avoid blocking legitimate assets. It will be added in a later
     * phase with dedicated testing.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $headers = [
            // Prevent MIME-type sniffing
            'X-Content-Type-Options' => 'nosniff',

            // Block framing — protects against clickjacking
            'X-Frame-Options' => 'SAMEORIGIN',

            // Control referrer information on outgoing links
            'Referrer-Policy' => 'strict-origin-when-cross-origin',

            // Deny browser features we don't use
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), payment=(), usb=(), browsing-topics=()',

            // Isolate browsing context
            'Cross-Origin-Opener-Policy' => 'same-origin-allow-popups',
        ];

        foreach ($headers as $key => $value) {
            if (! $response->headers->has($key)) {
                $response->headers->set($key, $value);
            }
        }

        // HSTS — only over HTTPS in non-local environments
        if ($request->secure() && ! app()->environment('local')) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains'
            );
        }

        return $response;
    }
}