<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function show(Request $request, string $notification): RedirectResponse
    {
        $notificationModel = $request->user()->notifications()->findOrFail($notification);

        if ($notificationModel->read_at === null) {
            $notificationModel->markAsRead();
        }

        return redirect()->to($notificationModel->data['url'] ?? route('dashboard'));
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update([
            'read_at' => now(),
        ]);

        return back();
    }
}
