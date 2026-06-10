<?php

namespace App\Services;

use App\Models\Requisition;
use App\Models\User;
use App\Notifications\RequisitionPendingApproval;
use App\Notifications\RequisitionStatusChanged;
use Illuminate\Support\Facades\Notification;
use InvalidArgumentException;

class RequisitionWorkflowService
{
    /**
     * Envía un borrador a aprobación (Borrador -> Pendiente).
     * Si el usuario tiene permiso '*', la requisición se aprueba automáticamente.
     *
     * @return array Resultado con claves 'status' y 'message'
     */
    public function submitForApproval(Requisition $requisition, User $user): array
    {
        if ($requisition->status !== 'borrador') {
            throw new InvalidArgumentException('Solo las requisiciones en borrador pueden enviarse a aprobación.');
        }

        $isAdmin = in_array('*', $user->role?->permissions ?? [], true);

        if ($isAdmin) {
            $requisition->update([
                'status' => 'aprobada',
                'approved_by' => $user->id,
            ]);

            $requisition->activities()->create([
                'user_id' => $user->id,
                'action' => 'approved',
                'description' => 'Aprobada automáticamente al solicitar.',
                'old_values' => ['status' => 'borrador'],
                'new_values' => ['status' => 'aprobada'],
            ]);

            if ($requisition->creator && $requisition->creator->id !== $user->id) {
                $requisition->creator->notify(
                    new RequisitionStatusChanged($requisition, 'borrador', 'aprobada', $user)
                );
            }

            return [
                'status' => 'approved_auto',
                'message' => 'Requisición aprobada automáticamente.',
            ];
        }

        $requisition->update(['status' => 'pendiente']);

        $requisition->activities()->create([
            'user_id' => $user->id,
            'action' => 'status_changed',
            'description' => 'Enviada a aprobación.',
            'old_values' => ['status' => 'borrador'],
            'new_values' => ['status' => 'pendiente'],
        ]);

        $approvers = User::getApprovers();
        Notification::send($approvers, new RequisitionPendingApproval($requisition));

        return [
            'status' => 'pending',
            'message' => 'Requisición enviada a aprobación.',
        ];
    }

    /**
     * Aprueba una requisición (Pendiente -> Aprobada).
     */
    public function approve(Requisition $requisition, User $user): void
    {
        if (! $user->hasPermission('requisiciones.aprobar') && ! $user->hasPermission('*')) {
            throw new InvalidArgumentException('No tienes permiso para aprobar requisiciones.');
        }

        if ($requisition->status !== 'pendiente') {
            throw new InvalidArgumentException('Solo las requisiciones pendientes pueden ser aprobadas.');
        }

        $requisition->update([
            'status' => 'aprobada',
            'approved_by' => $user->id,
        ]);

        $requisition->activities()->create([
            'user_id' => $user->id,
            'action' => 'approved',
            'description' => 'Requisición aprobada.',
            'old_values' => ['status' => 'pendiente'],
            'new_values' => ['status' => 'aprobada'],
        ]);

        if ($requisition->creator && $requisition->creator->id !== $user->id) {
            $requisition->creator->notify(
                new RequisitionStatusChanged($requisition, 'pendiente', 'aprobada', $user)
            );
        }
    }

    /**
     * Rechaza una requisición (Pendiente -> Rechazada).
     */
    public function reject(Requisition $requisition, User $user, string $comment): void
    {
        if (! $user->hasPermission('requisiciones.aprobar') && ! $user->hasPermission('*')) {
            throw new InvalidArgumentException('No tienes permiso para rechazar requisiciones.');
        }

        if ($requisition->status !== 'pendiente') {
            throw new InvalidArgumentException('Solo las requisiciones pendientes pueden ser rechazadas.');
        }

        if (trim($comment) === '') {
            throw new InvalidArgumentException('El motivo del rechazo es obligatorio.');
        }

        $requisition->update([
            'status' => 'rechazada',
            'approved_by' => $user->id,
            'rejection_comment' => $comment,
        ]);

        $requisition->activities()->create([
            'user_id' => $user->id,
            'action' => 'rejected',
            'description' => 'Requisición rechazada: ' . $comment,
            'old_values' => ['status' => 'pendiente'],
            'new_values' => ['status' => 'rechazada'],
        ]);

        if ($requisition->creator && $requisition->creator->id !== $user->id) {
            $requisition->creator->notify(
                new RequisitionStatusChanged($requisition, 'pendiente', 'rechazada', $user)
            );
        }
    }
}
