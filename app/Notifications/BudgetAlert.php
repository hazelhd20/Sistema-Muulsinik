<?php

namespace App\Notifications;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class BudgetAlert extends Notification implements ShouldBroadcast
{
    use Queueable;

    public function __construct(
        public Project $project,
        public float $percentageUsed,
        public string $severity = 'warning' // warning (80%) | danger (100%)
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
        $threshold = $this->severity === 'danger' ? '100%' : '80%';

        return [
            'type' => 'budget_alert',
            'title' => $this->severity === 'danger' ? 'Presupuesto excedido' : 'Alerta de presupuesto',
            'message' => "El proyecto '{$this->project->name}' ha alcanzado el {$threshold} del presupuesto (\${$this->percentageUsed}% usado)",
            'icon' => $this->severity === 'danger' ? 'alert-circle' : 'alert-triangle',
            'color' => $this->severity,
            'action_url' => url("/proyectos/{$this->project->id}"),
            'action_text' => 'Ver proyecto',
            'project_id' => $this->project->id,
            'project_name' => $this->project->name,
            'percentage_used' => $this->percentageUsed,
            'severity' => $this->severity,
        ];
    }
}
