<?php

namespace App\Providers;

use App\Models\GuestSetting;
use App\Services\GuestRequestGuard;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Standart til (har so'rovda SetLocale middleware foydalanuvchi tanloviga almashtiradi);
        // `uz` Carbon'da kirillcha — lotin yozuvi uz_Latn
        \App\Support\Locales::apply((string) config('app.locale', 'uz'));

        try {
            GuestSetting::applyToConfig();
        } catch (\Throwable) {
            // Jadval hali migratsiya qilinmagan bo'lsa .env qiymatlari ishlaydi
        }

        RateLimiter::for('guest-post', function (Request $request) {
            if (app(GuestRequestGuard::class)->isWhitelisted($request)) {
                return Limit::none();
            }

            return Limit::perMinute(config('guest_limits.post_per_minute'))->by($request->ip());
        });
    }
}
