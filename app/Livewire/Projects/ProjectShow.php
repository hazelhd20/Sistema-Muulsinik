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

    public Project $project;

    public function mount(int $id): void
    {
        $this->project = Project::with([
            'requisitions.items',
            'requisitions.vendor',
            'requisitions.creator',
            'expenses.user',
            'expenseAllocations.expense',
        ])->findOrFail($id);
    }

    #[Layout('components.layouts.app')]
    #[Title('Detalle de Proyecto')]
    public function render()
    {
        return view('livewire.projects.project-show');
    }
}
