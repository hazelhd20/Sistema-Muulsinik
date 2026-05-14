<?php

namespace App\Livewire;

use Illuminate\Notifications\DatabaseNotification;
use Livewire\Attributes\On;
use Livewire\Component;

class NotificationDropdown extends Component
{
    public bool $isOpen = false;
    public array $notifications = [];
    public int $unreadCount = 0;

    public function mount(): void
    {
        $this->loadNotifications();
    }

    public function loadNotifications(): void
    {
        $user = auth()->user();

        if (!$user) {
            $this->notifications = [];
            $this->unreadCount = 0;
            return;
        }

        $this->notifications = $user->notifications()
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn (DatabaseNotification $notification) => [
                'id' => $notification->id,
                'type' => $notification->data['type'] ?? 'info',
                'title' => $notification->data['title'] ?? 'Notificación',
                'message' => $notification->data['message'] ?? '',
                'icon' => $notification->data['icon'] ?? 'bell',
                'color' => $notification->data['color'] ?? 'primary',
                'action_url' => $notification->data['action_url'] ?? '#',
                'action_text' => $notification->data['action_text'] ?? 'Ver',
                'read_at' => $notification->read_at,
                'created_at' => $notification->created_at->diffForHumans(),
            ])
            ->toArray();

        $this->unreadCount = $user->unreadNotifications()->count();
    }

    public function toggle(): void
    {
        $this->isOpen = !$this->isOpen;
    }

    public function close(): void
    {
        $this->isOpen = false;
    }

    public function markAsRead(string $id): void
    {
        $notification = DatabaseNotification::find($id);

        if ($notification && $notification->notifiable_id === auth()->id()) {
            $notification->markAsRead();
            $this->loadNotifications();
        }
    }

    public function markAllAsRead(): void
    {
        auth()->user()?->unreadNotifications->markAsRead();
        $this->loadNotifications();
    }

    #[On('notification-received')]
    public function refreshNotifications(): void
    {
        $this->loadNotifications();
    }

    public function render()
    {
        return view('livewire.notification-dropdown');
    }
}
