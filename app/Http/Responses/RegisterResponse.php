<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;

class RegisterResponse implements RegisterResponseContract
{
    public function toResponse($request)
    {
        if ($request->wantsJson()) {
            return new JsonResponse('', 201);
        }

        $user = auth()->user();

        if (! $user) {
            return redirect('/');
        }

        // Staff → admin dashboard
        if ($user->isStaff()) {
            return redirect()->intended(route('admin.dashboard'));
        }

        // Customers → today's menu
        return redirect()->route('menu.index');
    }
}