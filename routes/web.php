<?php

use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\DispatchController;
use App\Http\Controllers\Admin\SlaSettingsController;
use App\Http\Controllers\Admin\UserApprovalController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExecutorTicketController;
use App\Http\Controllers\GuestTicketController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ManagerDashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OperatorTicketController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/pending-approval', [HomeController::class, 'pendingApproval'])->name('pending-approval');
Route::get('/_errors/{code}', function (string $code) {
    $views = [
        '403' => 'errors.403',
        '404' => 'errors.404',
        '413' => 'errors.post-too-large',
        '419' => 'errors.419',
        '429' => 'errors.429',
        '500' => 'errors.500',
        '503' => 'errors.503',
    ];

    abort_unless(array_key_exists($code, $views), 404);

    return response()->view($views[$code], [
        'maxSize' => '25 MB',
        'serverLimit' => ini_get('post_max_size') ?: '32M',
    ], (int) $code);
})->whereNumber('code')->name('errors.preview');

Route::prefix('guest')->name('guest.')->group(function () {
    Route::get('/create', [GuestTicketController::class, 'create'])->name('create');
    Route::post('/create', [GuestTicketController::class, 'store'])->name('store');
    Route::get('/track', [GuestTicketController::class, 'track'])->name('track');
    Route::post('/track', [GuestTicketController::class, 'lookup'])->name('lookup');
    Route::get('/track/{ticket}', [GuestTicketController::class, 'show'])->name('tickets.show');
});

Route::middleware(['auth', 'approved'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::post('/read-all', [NotificationController::class, 'markAllRead'])->name('read-all');
        Route::post('/clear-all', [NotificationController::class, 'destroyAll'])->name('clear-all');
        Route::delete('/{notification}', [NotificationController::class, 'destroy'])->name('destroy');
        Route::get('/{notification}', [NotificationController::class, 'show'])->name('show');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::prefix('tickets')->name('tickets.')->middleware('role:requester')->group(function () {
        Route::get('/', [TicketController::class, 'index'])->name('index');
        Route::get('/create', [TicketController::class, 'create'])->name('create');
        Route::post('/', [TicketController::class, 'store'])->name('store');
        Route::get('/{ticket}', [TicketController::class, 'show'])->name('show');
        Route::post('/{ticket}/comments', [TicketController::class, 'comment'])->name('comment');
    });

    Route::prefix('operator/tickets')->name('operator.tickets.')->middleware('role:operator')->group(function () {
        Route::get('/', [OperatorTicketController::class, 'index'])->name('index');
        Route::get('/create', [OperatorTicketController::class, 'create'])->name('create');
        Route::post('/', [OperatorTicketController::class, 'store'])->name('store');
        Route::get('/{ticket}', [OperatorTicketController::class, 'show'])->name('show');
    });

    Route::prefix('executor/tickets')->name('executor.tickets.')->middleware('role:executor')->group(function () {
        Route::get('/', [ExecutorTicketController::class, 'index'])->name('index');
        Route::get('/archive', [ExecutorTicketController::class, 'archive'])->name('archive');
        Route::get('/{ticket}', [ExecutorTicketController::class, 'show'])->name('show');
        Route::post('/{ticket}/start', [ExecutorTicketController::class, 'start'])->name('start');
        Route::post('/{ticket}/complete', [ExecutorTicketController::class, 'complete'])->name('complete');
        Route::post('/{ticket}/return', [ExecutorTicketController::class, 'requestReturn'])->name('return');
        Route::post('/{ticket}/comment', [ExecutorTicketController::class, 'comment'])->name('comment');
    });

    Route::middleware('role:admin|manager')->group(function () {
        Route::get('/manager/dashboard', ManagerDashboardController::class)->name('manager.dashboard');
        Route::get('/manager/dashboard/export/{stat}/{format}', [ManagerDashboardController::class, 'export'])
            ->name('manager.dashboard.export');
    });

    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
        Route::get('/dispatch', [DispatchController::class, 'index'])->name('dispatch.index');
        Route::get('/dispatch/export/csv', [DispatchController::class, 'exportCsv'])->name('dispatch.export');
        Route::get('/dispatch/{ticket}', [DispatchController::class, 'show'])->name('dispatch.show');
        Route::post('/dispatch/{ticket}/assign', [DispatchController::class, 'assign'])->name('dispatch.assign');
        Route::post('/dispatch/{ticket}/reject', [DispatchController::class, 'reject'])->name('dispatch.reject');
        Route::post('/dispatch/{ticket}/close', [DispatchController::class, 'close'])->name('dispatch.close');
        Route::post('/dispatch/{ticket}/comment', [DispatchController::class, 'comment'])->name('dispatch.comment');

        Route::get('/users', [UserApprovalController::class, 'index'])->name('users.index');
        Route::patch('/users/{user}', [UserApprovalController::class, 'update'])->name('users.update');

        Route::get('/departments', [DepartmentController::class, 'index'])->name('departments.index');
        Route::post('/departments', [DepartmentController::class, 'store'])->name('departments.store');
        Route::patch('/departments/{department}', [DepartmentController::class, 'update'])->name('departments.update');

        Route::get('/settings/sla', [SlaSettingsController::class, 'index'])->name('sla.index');
        Route::post('/settings/sla/bootstrap-defaults', [SlaSettingsController::class, 'bootstrapDefaults'])->name('sla.bootstrap');
        Route::put('/settings/sla', [SlaSettingsController::class, 'update'])->name('sla.update');
    });
});

require __DIR__.'/auth.php';
