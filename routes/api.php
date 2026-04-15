<?php

use App\Http\Controllers\Api\KpiAuthController;
use App\Http\Controllers\Api\KpiDashboardController;
use App\Http\Middleware\KpiApiCors;
use Illuminate\Support\Facades\Route;

Route::middleware(KpiApiCors::class)->group(function (): void {
    Route::options('/{any}', fn () => response()->noContent())->where('any', '.*');

    Route::post('/auth/login', [KpiAuthController::class, 'login']);
    Route::get('/auth/me', [KpiAuthController::class, 'me']);
    Route::post('/auth/logout', [KpiAuthController::class, 'logout']);

    Route::get('/kpi/dashboard', KpiDashboardController::class);
});
