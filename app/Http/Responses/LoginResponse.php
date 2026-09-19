<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        if ($request->wantsJson()) {
            return new JsonResponse('', 204);
        }

        $user = auth()->user();

        if (! $user) {
            return redirect('/');
        }

        // Staff (Admin/Manager/Kitchen/Delivery) → admin dashboard
        if ($user->isStaff()) {
            return redirect()->intended(route('admin.dashboard'));
        }

        // Customers → wallet, always (ignore any intended URL that would 403 them)
        return redirect('/wallet');
    }
}