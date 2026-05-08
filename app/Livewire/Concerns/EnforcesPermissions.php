<?php

namespace App\Livewire\Concerns;

trait EnforcesPermissions
{
    /**
     * Verifica si el usuario autenticado tiene el permiso dado.
     * Si no lo tiene, emite un flash de error y retorna true (para que el método aborte).
     */
    protected function denyUnless(string $permission, string $message = 'No tienes permiso para realizar esta acción.'): bool
    {
        if (!auth()->user()?->hasPermission($permission)) {
            session()->flash('error', $message);
            return true;
        }
        return false;
    }
}
