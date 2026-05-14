<?php

namespace App\Notifications;

use App\Models\Document;
use App\Models\Quotation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class QuotationProcessed extends Notification
{
    use Queueable;

    public function __construct(
        public Document $document,
        public ?Quotation $quotation = null,
        public bool $success = true,
        public ?string $errorMessage = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        if ($this->success) {
            return [
                'type' => 'quotation_processed',
                'title' => 'Cotización procesada exitosamente',
                'message' => "El archivo '{$this->document->original_name}' ha sido procesado y convertido en requisición",
                'icon' => 'file-check',
                'color' => 'success',
                'action_url' => url("/requisiciones"),
                'action_text' => 'Ver requisición',
                'document_id' => $this->document->id,
                'document_name' => $this->document->original_name,
            ];
        }

        return [
            'type' => 'quotation_failed',
            'title' => 'Error al procesar cotización',
            'message' => $this->errorMessage ?? "No se pudo procesar '{$this->document->original_name}'. Requiere revisión manual.",
            'icon' => 'file-x',
            'color' => 'danger',
            'action_url' => url("/requisiciones/subir-cotizacion"),
            'action_text' => 'Reintentar',
            'document_id' => $this->document->id,
            'document_name' => $this->document->original_name,
        ];
    }
}
