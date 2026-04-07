<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsApproved
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->isApproved()) {
            return $next($request);
        }

        if ($request->routeIs('pending-approval', 'logout', 'profile.*')) {
            return $next($request);
        }

        return redirect()->route('pending-approval');
    }
}
