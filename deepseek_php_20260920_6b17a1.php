    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'staff' => \App\Http\Middleware\RedirectCustomersFromAdmin::class,
            'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        ]);

        // Apply security headers to every web request
        $middleware->web(append: [
            \App\Http\Middleware\SecurityHeaders::class,
        ]);
    })