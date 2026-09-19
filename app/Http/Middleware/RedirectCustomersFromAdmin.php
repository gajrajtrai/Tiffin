<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectCustomersFromAdmin
{
    /**
     * Prevent customers from ever reaching the admin area.
     * Staff pass through to the per-route permission check.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->isStaff()) {
            return redirect('/');
        }

        return $next($request);
    }
}