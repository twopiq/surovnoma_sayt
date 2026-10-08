<?php

namespace App\Http\Controllers;

use App\Support\Locales;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function __invoke(Request $request, string $locale): RedirectResponse
    {
        abort_unless(Locales::isSupported($locale), 404);

        $request->session()->put(Locales::SESSION_KEY, $locale);

        if ($user = $request->user()) {
            $user->forceFill(['locale' => $locale])->save();
        }

        return back();
    }
}
