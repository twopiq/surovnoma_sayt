<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class KpiApiCors
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $request->isMethod('OPTIONS')
            ? response()->noContent()
            : $next($request);

        $origin = $request->headers->get('Origin');

        if (! $origin) {
            return $response;
        }

        if (! $this->originIsAllowed($origin)) {
            return $response;
        }

        $response->headers->set('Access-Control-Allow-Origin', $origin);
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, X-Requested-With, Accept, Authorization');
        $response->headers->set('Access-Control-Max-Age', '86400');
        $response->headers->set('Vary', 'Origin');

        return $response;
    }

    private function originIsAllowed(string $origin): bool
    {
        $allowedOrigins = array_filter(array_map(
            static fn (string $value): string => trim($value),
            explode(',', (string) env('KPI_API_ALLOWED_ORIGINS', '*')),
        ));

        return in_array('*', $allowedOrigins, true)
            || in_array($origin, $allowedOrigins, true);
    }
}
