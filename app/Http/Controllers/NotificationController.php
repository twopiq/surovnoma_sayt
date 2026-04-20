<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        return view('notifications.index', [
            'notifications' => $request->user()
                ->notifications()
                ->latest()
                ->paginate(12),
            'unreadCount' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function feed(Request $request): JsonResponse
    {
        $notifications = $request->user()
            ->unreadNotifications()
            ->latest()
            ->take(20)
            ->get()
            ->map(fn ($notification) => [
                'id' => $notification->id,
                'title' => $notification->data['title'] ?? 'Bildirishnoma',
                'body' => $notification->data['body'] ?? '',
                'href' => route('notifications.show', $notification->id),
                'created_at' => $notification->created_at?->diffForHumans(),
            ])
            ->values();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function show(Request $request, string $notification): RedirectResponse
    {
        $notificationModel = $request->user()->notifications()->findOrFail($notification);

        if ($notificationModel->read_at === null) {
            $notificationModel->markAsRead();
        }

        return redirect()->to($notificationModel->data['url'] ?? route('app.home'));
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update([
            'read_at' => now(),
        ]);

        return back()->with('notifications_open', true)->with('status', 'notifications-read');
    }

    public function destroy(Request $request, string $notification): RedirectResponse
    {
        $request->user()->notifications()->findOrFail($notification)->delete();

        return back()->with('notifications_open', true)->with('status', 'notification-deleted');
    }

    public function destroyAll(Request $request): RedirectResponse
    {
        $request->user()->notifications()->delete();

        return back()->with('notifications_open', true)->with('status', 'notifications-cleared');
    }
}
