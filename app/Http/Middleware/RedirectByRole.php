<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectByRole
{
    /**
     * Redirect authenticated users to their role-specific dashboard.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            $user = $request->user();

            if ($user->hasAnyRole(['super_admin', 'exam_officer', 'ict_admin'])) {
                return redirect()->route('admin.dashboard');
            }

            if ($user->hasRole('invigilator')) {
                return redirect()->route('invigilator.scanner');
            }

            if ($user->hasRole('student')) {
                return redirect()->route('student.dashboard');
            }
        }

        return $next($request);
    }
}
