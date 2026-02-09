<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Config;

class HandleAdminSession
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // If the request path starts with api/v1/admin or admin, use the admin session cookie
        if ($request->is('api/v1/admin*') || $request->is('admin*')) {
            Config::set('session.cookie', 'admin_session');
        }

        return $next($request);
    }
}
