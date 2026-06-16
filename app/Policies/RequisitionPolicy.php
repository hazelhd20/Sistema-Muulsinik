<?php

namespace App\Policies;

use App\Models\Requisition;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class RequisitionPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('requisiciones.ver');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Requisition $requisition): bool
    {
        return $user->hasPermission('requisiciones.ver');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('requisiciones.crear');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Requisition $requisition): bool
    {
        return $user->hasPermission('requisiciones.editar');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Requisition $requisition): bool
    {
        return $user->hasPermission('requisiciones.eliminar');
    }

    /**
     * Determine whether the user can approve the model.
     */
    public function approve(User $user, Requisition $requisition): bool
    {
        return $user->hasPermission('requisiciones.aprobar');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Requisition $requisition): bool
    {
        return $user->hasPermission('requisiciones.eliminar');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Requisition $requisition): bool
    {
        return $user->hasPermission('requisiciones.eliminar');
    }
}
