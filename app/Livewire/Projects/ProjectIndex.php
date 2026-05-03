<?php

namespace App\Livewire\Projects;

use App\Models\Project;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

class ProjectIndex extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public bool $showCreateModal = false;

    // Campos del formulario de creación
    public string $name = '';
    public string $description = '';
    public string $client = '';
    public string $budget = '';
    public string $startDate = '';
    public string $endDate = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->showCreateModal = true;
    }

    public function createProject(): void
    {
        $validated = $this->validate([
            'name' => 'required|min:3|max:255',
            'description' => 'nullable|max:1000',
            'client' => 'nullable|max:255',
            'budget' => 'required|numeric|min:0',
            'startDate' => 'nullable|date',
            'endDate' => 'nullable|date|after_or_equal:startDate',
        ]);

        Project::create([
            'name' => $this->name,
            'description' => $this->description,
            'client' => $this->client,
            'budget' => $this->budget,
            'start_date' => $this->startDate ?: null,
            'end_date' => $this->endDate ?: null,
            'status' => 'activo',
        ]);

        $this->showCreateModal = false;
        $this->resetForm();
        session()->flash('success', 'Proyecto creado exitosamente.');
    }

    public function deleteProject(int $projectId): void
    {
        Project::findOrFail($projectId)->delete();
        session()->flash('success', 'Proyecto eliminado.');
    }

    private function resetForm(): void
    {
        $this->name = '';
        $this->description = '';
        $this->client = '';
        $this->budget = '';
        $this->startDate = '';
        $this->endDate = '';
    }

    #[Layout('components.layouts.app')]
    #[Title('Proyectos')]
    public function render()
    {
        $projects = Project::query()
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('client', 'like', "%{$this->search}%"))
            ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
            ->latest()
            ->paginate(12);

        return view('livewire.projects.project-index', compact('projects'));
    }
}
