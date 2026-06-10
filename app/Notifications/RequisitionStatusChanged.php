<?php

namespace App\Notifications;

use App\Models\Requisition;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class RequisitionStatusChanged extends Notification
{
    use Queueable;

    public function __construct(
        public Requisition $requisition,
        public string $oldStatus,
        public string $newStatus,
        public ?User $actor = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $statusLabels = [
            'pendiente' => 'Pendiente',
            'aprobada' => 'Aprobada',
            'rechazada' => 'Rechazada',
            'en_proceso' => 'En proceso',
            'completada' => 'Completada',
        ];

        $actorName = $this->actor?->name ?? 'Sistema';

        $colorMap = [
            'aprobada' => 'success',
            'rechazada' => 'danger',
            'pendiente' => 'warning',
            'en_proceso' => 'primary',
            'completada' => 'success',
        ];

        $iconMap = [
            'aprobada' => 'check-circle',
            'rechazada' => 'x-circle',
            'pendiente' => 'clock',
            'en_proceso' => 'loader',
            'completada' => 'check-check',
        ];

        return [
            'type' => 'requisition_status',
            'title' => "Requisición {$statusLabels[$this->newStatus]}",
            'message' => "{$actorName} cambió el estado de {$this->requisition->number} a {$statusLabels[$this->newStatus]}",
            'icon' => $iconMap[$this->newStatus] ?? 'info',
            'color' => $colorMap[$this->newStatus] ?? 'primary',
            'action_url' => url('/requisiciones'),
            'action_text' => 'Ver detalle',
            'requisition_id' => $this->requisition->id,
            'requisition_number' => $this->requisition->number,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
        ];
    }
}
