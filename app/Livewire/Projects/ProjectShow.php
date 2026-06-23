<?php

namespace App\Livewire\Projects;

use App\Livewire\Concerns\EnforcesPermissions;
use App\Models\Project;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

class ProjectShow extends Component
{
    use EnforcesPermissions;

    public int $projectId;

    public function mount(int $id): void
    {
        if (! auth()->user()?->hasPermission('proyectos.ver') && ! auth()->user()?->hasPermission('*')) {
            abort(403, 'No tienes permiso para ver proyectos.');
        }

        $this->projectId = $id;
    }

    #[Layout('components.layouts.app')]
    #[Title('Detalle de Proyecto')]
    public function render()
    {
        $project = Project::with([
            'client',
            'requisitions.items.supplier',
            'requisitions.quotations.supplier',
            'requisitions.vendor.supplier',
            'requisitions.creator',
            'expenses.user',
            'expenseAllocations.expense',
        ])->findOrFail($this->projectId);

        return view('livewire.projects.project-show', compact('project'));
    }
}
