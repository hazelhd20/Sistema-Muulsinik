<?php

namespace App\Livewire;

use App\Models\Project;
use Livewire\Component;

/**
 * Selector global de proyecto activo (RF-AUTH-03).
 *
 * Permite al usuario seleccionar un proyecto activo desde la barra de navegación
 * superior. Todas las operaciones de registro (gastos, requisiciones, documentos)
 * tomarán este proyecto por defecto. Se almacena en sesión para persistir
 * entre recargas sin necesidad de cerrar sesión.
 */
class ProjectSelector extends Component
{
    public $activeProjectId = '';

    public function mount(): void
    {
        $this->activeProjectId = session('active_project_id', '');
    }

    public function updatedActiveProjectId($value): void
    {
        session(['active_project_id' => $value ?: null]);

        // Dispatch browser event para que otros componentes Livewire puedan reaccionar
        $this->dispatch('active-project-changed', projectId: $value);
    }

    public function render()
    {
        $projects = Project::where('status', 'activo')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('livewire.project-selector', compact('projects'));
    }
}
