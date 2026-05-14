<?php

namespace App\Notifications;

use App\Models\Requisition;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class RequisitionPendingApproval extends Notification
{
    use Queueable;

    public function __construct(
        public Requisition $requisition
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'requisition_pending',
            'title' => 'Requisición pendiente de aprobación',
            'message' => "La requisición {$this->requisition->number} requiere tu aprobación",
            'icon' => 'clipboard-list',
            'color' => 'primary',
            'action_url' => url("/requisiciones"),
            'action_text' => 'Ver requisiciones',
            'requisition_id' => $this->requisition->id,
            'requisition_number' => $this->requisition->number,
        ];
    }
}
