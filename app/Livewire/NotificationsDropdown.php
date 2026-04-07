<?php

namespace App\Livewire;

use Livewire\Component;

class NotificationsDropdown extends Component
{
    public function render()
    {
        $user = auth()->user();

        return view('livewire.notifications-dropdown', [
            'notifications' => $user?->notifications()->latest()->take(6)->get() ?? collect(),
            'unreadCount' => $user?->unreadNotifications()->count() ?? 0,
        ]);
    }
}
