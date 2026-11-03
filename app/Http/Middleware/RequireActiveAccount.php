<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequireActiveAccount
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if ($user && ! $request->routeIs('logout')) {
            abort_unless($user->is_active, 403, 'Akun tidak aktif.');
            abort_unless($user->isAdmin() || ($user->housing_id && $user->housing?->is_active), 403, 'Perumahan tidak aktif.');
        }

        return $next($request);
    }
}
