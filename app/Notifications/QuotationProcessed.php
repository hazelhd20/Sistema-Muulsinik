<?php

namespace App\Notifications;

use App\Models\Quotation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class QuotationProcessed extends Notification implements ShouldBroadcast
{
    use Queueable;

    public function __construct(
        public Quotation $quotation,
        public bool $success = true,
        public ?string $errorMessage = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toDatabase($notifiable));
    }

    public function toDatabase(object $notifiable): array
    {
        if ($this->success) {
            return [
                'type' => 'quotation_processed',
                'title' => 'Cotización procesada exitosamente',
                'message' => "El archivo '{$this->quotation->original_filename}' ha sido procesado",
                'icon' => 'file-check',
                'color' => 'success',
                'action_url' => "/requisiciones/subir-cotizacion?ids[0]={$this->quotation->id}",
                'action_text' => 'Ver',
                'quotation_id' => $this->quotation->id,
                'quotation_filename' => $this->quotation->original_filename,
            ];
        }

        return [
            'type' => 'quotation_failed',
            'title' => 'Error al procesar cotización',
            'message' => $this->errorMessage ?? "No se pudo procesar '{$this->quotation->original_filename}'. Requiere revisión manual.",
            'icon' => 'file-x',
            'color' => 'danger',
            'action_url' => "/requisiciones/subir-cotizacion?ids[0]={$this->quotation->id}",
            'action_text' => 'Reintentar',
            'quotation_id' => $this->quotation->id,
            'quotation_filename' => $this->quotation->original_filename,
        ];
    }
}
