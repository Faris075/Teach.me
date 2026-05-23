<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MultiTenantMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        // Super admin bypasses all tenant scoping
        if ($user->role === 'super_admin') {
            return $next($request);
        }

        // Independent tutors are scoped by teacher_id — store in request for query use
        if ($user->is_independent) {
            $request->merge(['tenant_teacher_id' => $user->id]);
            return $next($request);
        }

        // Institutional users must have a school_id
        if (! $user->school_id) {
            abort(403, 'No school context found for this account.');
        }

        $request->merge(['tenant_school_id' => $user->school_id]);

        return $next($request);
    }
}
