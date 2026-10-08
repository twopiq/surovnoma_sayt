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
        // Sana matnlari ("8 soniya avval", oy nomlari) lotin yozuvida — `uz` Carbon'da kirillcha
        if (str_starts_with((string) config('app.locale'), 'uz')) {
            \Illuminate\Support\Carbon::setLocale('uz_Latn');
        }

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
