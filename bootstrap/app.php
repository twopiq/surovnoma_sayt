<?php

use App\Support\TicketFileUpload;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withCommands()
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule) {
        if (config('database-backup.enabled', true)) {
            $schedule->command('db:backup --label=scheduled')
                ->everySixHours()
                ->withoutOverlapping();
        }

        $schedule->command('tickets:send-deadline-alerts')
            ->everyTenMinutes()
            ->withoutOverlapping();

        $schedule->command('notifications:purge-expired')
            ->dailyAt('00:00')
            ->withoutOverlapping();
    })
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'approved' => \App\Http\Middleware\EnsureUserIsApproved::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (PostTooLargeException $exception, Request $request) {
            return response()->view('errors.post-too-large', [
                'maxSize' => TicketFileUpload::maxTotalSizeLabel(),
                'maxFileSize' => TicketFileUpload::maxFileSizeLabel(),
                'maxFiles' => TicketFileUpload::MAX_FILES,
                'serverLimit' => ini_get('post_max_size') ?: '32M',
            ], 413);
        });
    })->create();
