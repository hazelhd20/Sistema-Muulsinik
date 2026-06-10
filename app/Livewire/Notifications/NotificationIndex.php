<?php

namespace App\Livewire\Notifications;

use Illuminate\Notifications\DatabaseNotification;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

class NotificationIndex extends Component
{
    use WithPagination;

    public string $filter = 'all'; // all, unread, read

    public function mount(): void
    {
        // Mark all as read when visiting the page
        auth()->user()?->unreadNotifications->markAsRead();
    }

    public function updatedFilter(): void
    {
        $this->resetPage();
    }

    public function getNotificationsProperty()
    {
        $query = auth()->user()?->notifications() ?? DatabaseNotification::query()->whereNull('id');

        if ($this->filter === 'unread') {
            $query->whereNull('read_at');
        } elseif ($this->filter === 'read') {
            $query->whereNotNull('read_at');
        }

        return $query->latest()->paginate(20);
    }

    public function markAsRead(string $id): void
    {
        $notification = DatabaseNotification::find($id);

        if ($notification && $notification->notifiable_id === auth()->id()) {
            $notification->markAsRead();
        }
    }

    public function markAsUnread(string $id): void
    {
        $notification = DatabaseNotification::find($id);

        if ($notification && $notification->notifiable_id === auth()->id()) {
            $notification->update(['read_at' => null]);
        }
    }

    public function delete(string $id): void
    {
        $notification = DatabaseNotification::find($id);

        if ($notification && $notification->notifiable_id === auth()->id()) {
            $notification->delete();
        }

        $this->dispatch('toast', [
            'icon' => 'success',
            'title' => 'Notificación eliminada',
        ]);
    }

    public function markAllAsRead(): void
    {
        auth()->user()?->unreadNotifications->markAsRead();
        $this->dispatch('toast', [
            'icon' => 'success',
            'title' => 'Todas las notificaciones marcadas como leídas',
        ]);
    }

    public function deleteAll(): void
    {
        auth()->user()?->notifications()->delete();
        $this->dispatch('toast', [
            'icon' => 'success',
            'title' => 'Todas las notificaciones eliminadas',
        ]);
    }

    #[Layout('components.layouts.app')]
    #[Title('Notificaciones')]
    public function render()
    {
        return view('livewire.notifications.notification-index');
    }
}
