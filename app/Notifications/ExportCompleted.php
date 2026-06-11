<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class ExportCompleted extends Notification implements ShouldBroadcast
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public string $fileName,
        public string $downloadUrl,
        public string $message = 'Tu exportación está lista para descargar.'
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'type' => 'export_completed',
            'title' => 'Exportación finalizada',
            'message' => $this->message,
            'icon' => 'download',
            'color' => 'success',
            'action_url' => $this->downloadUrl,
            'action_text' => 'Descargar',
            'file_name' => $this->fileName,
        ]);
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'export_completed',
            'title' => 'Exportación finalizada',
            'message' => $this->message,
            'icon' => 'download',
            'color' => 'success',
            'action_url' => $this->downloadUrl,
            'action_text' => 'Descargar',
            'file_name' => $this->fileName,
        ];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->line('The introduction to the notification.')
            ->action('Notification Action', url('/'))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
