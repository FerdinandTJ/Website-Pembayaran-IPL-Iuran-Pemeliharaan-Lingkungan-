<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'same-origin');
        $existing = $response->headers->get('Content-Security-Policy');
        $response->headers->set('Content-Security-Policy', ($existing ? $existing.'; ' : '')."frame-ancestors 'none'; object-src 'none'; base-uri 'self'; form-action 'self'");
        if ($request->user()) {
            $response->headers->set('Cache-Control', 'private, no-store');
        }
        if (app()->environment('production') && $request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000');
        }

        return $response;
    }
}
