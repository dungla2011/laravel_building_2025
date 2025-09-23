<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'admin/*',
        'api/*',
        'admin/role-permissions/*',
        'admin/role-permissions/update',
    ];

    /**
     * Determine if the request has a URI that should pass through CSRF verification.
     */
    protected function inExceptArray($request): bool
    {
        // Always disable CSRF verification in testing environment
        if (app()->environment('testing')) {
            return true;
        }

        return parent::inExceptArray($request);
    }

    /**
     * Handle the request - bypass CSRF in testing
     */
    public function handle($request, \Closure $next)
    {
        if (app()->environment('testing')) {
            // Skip CSRF verification entirely in testing
            return $next($request);
        }

        return parent::handle($request, $next);
    }
}