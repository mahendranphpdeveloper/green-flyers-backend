<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\AdminData;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && $request->user() instanceof AdminData) {
            // Check if session has the current password hash (for session invalidation on password change)
            $sessionHash = $request->session()->get('admin_password_hash');
            $userHash = $request->user()->password;

            Log::info('AdminMiddleware Check', [
                'session_hash' => $sessionHash,
                'user_hash' => $userHash,
                'match' => $sessionHash === $userHash
            ]);

            if ($sessionHash !== $userHash) {
                Auth::guard('admin')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return response()->json([
                    'success' => false,
                    'message' => 'Session expired or password changed. Please login again.'
                ], 401);
            }

            return $next($request);
        }

        return response()->json([
            'success' => false,
            'message' => 'Unauthorized access. Admin only.'
        ], 403);
    }
}
