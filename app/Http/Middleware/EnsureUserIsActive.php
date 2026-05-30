<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * Block requests from deactivated users.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && $request->user()->is_active === false) {
            auth()->logout();

            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Your account has been deactivated.'], 403);
            }

            return redirect()->route('login')
                ->withErrors(['email' => 'Your account has been deactivated. Please contact the administrator.']);
        }

        return $next($request);
    }
}
