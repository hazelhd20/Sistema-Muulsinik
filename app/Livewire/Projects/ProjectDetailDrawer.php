<?php

namespace App\Livewire\Projects;

use App\Models\Project;
use Livewire\Attributes\On;
use Livewire\Component;

class ProjectDetailDrawer extends Component
{
    public bool $showDetailDrawer = false;

    public ?int $showingDetailId = null;

    public ?Project $detailProject = null;

    #[On('open-project-detail')]
    public function showDetail(int $id): void
    {
        $this->showingDetailId = $id;
        $this->detailProject = Project::with('client')->find($id);
        $this->showDetailDrawer = true;
    }

    public function render()
    {
        return view('livewire.projects.project-detail-drawer');
    }
}
