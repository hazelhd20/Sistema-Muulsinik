<?php

namespace App\Livewire\Concerns;

trait WithPerPagePagination
{
    public int $perPage = 10;

    /**
     * Hook de ciclo de vida de Livewire: se ejecuta al montar el componente.
     * Recupera la preferencia de paginación guardada en la sesión del usuario.
     */
    public function mountWithPerPagePagination(): void
    {
        $this->perPage = (int) session()->get('erp_per_page', 10);
    }

    /**
     * Se ejecuta automáticamente cuando el usuario cambia el selector en la UI.
     */
    public function updatedPerPage($value): void
    {
        session()->put('erp_per_page', (int) $value);

        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
    }
}
