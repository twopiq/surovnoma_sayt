<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** "Ingliz tili" bo'limi faqat ruxsat berilgan akkauntga ochiq (`php artisan english:access`). */
class EnsureEnglishAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->canAccessEnglish(), 403);

        return $next($request);
    }
}
