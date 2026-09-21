<?php

use Illuminate\Support\Str;

return [
    'driver' => env('SESSION_DRIVER', 'database'),
    'lifetime' => (int) env('SESSION_LIFETIME', 120),
    'expire_on_close' => env('SESSION_EXPIRE_ON_CLOSE', false),
    'encrypt' => env('SESSION_ENCRYPT', true),
    'cookie' => env('SESSION_COOKIE', Str::slug(env('APP_NAME', 'Tamkulay Chula')).'-session'),
    'path' => env('SESSION_PATH', '/'),
    'domain' => env('SESSION_DOMAIN'),
    'secure' => env('SESSION_SECURE_COOKIE', true),  // Enforce HTTPS
    'http_only' => env('SESSION_HTTP_ONLY', true), // Prevent JS access
    'same_site' => env('SESSION_SAME_SITE', 'lax'), // CSRF mitigation
    'partitioned' => env('SESSION_PARTITIONED_COOKIE', false),
];
