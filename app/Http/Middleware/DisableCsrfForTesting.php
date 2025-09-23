<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Disable CSRF verification in testing environment
 * This allows automated tests to make POST requests without CSRF tokens
 */
class DisableCsrfForTesting
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only disable CSRF in testing environment
        if (app()->environment('testing')) {
            // Remove CSRF token verification for testing
            $request->session()->regenerateToken();
        }

        return $next($request);
    }
}