<?php

namespace App\Http\Middleware;

use App\Support\Locales;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Til: foydalanuvchi profilidagi tanlov → sessiya → standart (config app.locale).
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->user()?->locale;

        if (! Locales::isSupported($locale) && $request->hasSession()) {
            $locale = $request->session()->get(Locales::SESSION_KEY);
        }

        Locales::apply(Locales::isSupported($locale) ? $locale : (string) config('app.locale', 'uz'));

        return $next($request);
    }
}
