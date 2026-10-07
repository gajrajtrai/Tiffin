<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class DashboardRedirectController extends Controller
{
    /**
     * Role-aware dashboard redirect.
     * Staff → admin dashboard. Customers → home (menu landing).
     */
    public function __invoke(): RedirectResponse
    {
        return auth()->user()?->isStaff()
            ? redirect()->route('admin.dashboard')
            : redirect('/');
    }
}