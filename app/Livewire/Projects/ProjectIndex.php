<?php

namespace App\Livewire\Projects;

use App\Livewire\Concerns\EnforcesPermissions;
use App\Models\Project;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

class ProjectIndex extends Component
{
    use WithPagination, EnforcesPermissions;

    public string $search = '';
    public string $statusFilter = '';
    public bool $showCreateModal = false;
    public bool $showEditModal = false;
    public ?int $editingId = null;

    // Campos del formulario (creación y edición comparten las mismas propiedades)
    public string $name = '';
    public string $description = '';
    public string $client = '';
    public string $budget = '';
    public string $startDate = '';
    public string $endDate = '';
    public string $status = 'activo';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->showCreateModal = true;
    }

    public function openEditModal(int $projectId): void
    {
        $project = Project::findOrFail($projectId);
        $this->editingId = $project->id;
        $this->name = $project->name;
        $this->description = $project->description ?? '';
        $this->client = $project->client ?? '';
        $this->budget = (string) $project->budget;
        $this->startDate = $project->start_date?->format('Y-m-d') ?? '';
        $this->endDate = $project->end_date?->format('Y-m-d') ?? '';
        $this->status = $project->status;
        $this->showEditModal = true;
    }

    public function createProject(): void
    {
        if ($this->denyUnless('proyectos.crear', 'No tienes permiso para crear proyectos.'))
            return;

        $this->validate([
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
        $this->dispatch('toast', ['icon' => 'success', 'message' => 'Proyecto creado exitosamente.']);
    }

    public function updateProject(): void
    {
        if ($this->denyUnless('proyectos.editar', 'No tienes permiso para editar proyectos.'))
            return;

        $this->validate([
            'name' => 'required|min:3|max:255',
            'description' => 'nullable|max:1000',
            'client' => 'nullable|max:255',
            'budget' => 'required|numeric|min:0',
            'startDate' => 'nullable|date',
            'endDate' => 'nullable|date|after_or_equal:startDate',
            'status' => 'required|in:activo,en_pausa,completado,cancelado',
        ]);

        Project::findOrFail($this->editingId)->update([
            'name' => $this->name,
            'description' => $this->description,
            'client' => $this->client,
            'budget' => $this->budget,
            'start_date' => $this->startDate ?: null,
            'end_date' => $this->endDate ?: null,
            'status' => $this->status,
        ]);

        $this->showEditModal = false;
        $this->resetForm();
        $this->dispatch('toast', ['icon' => 'success', 'message' => 'Proyecto actualizado correctamente.']);
    }

    public function deleteProject(int $projectId): void
    {
        if ($this->denyUnless('proyectos.eliminar', 'No tienes permiso para eliminar proyectos.'))
            return;

        $project = Project::findOrFail($projectId);

        $hasDependencies = \App\Models\Requisition::where('project_id', $projectId)->exists() ||
            \App\Models\Expense::where('project_id', $projectId)->exists() ||
            \App\Models\Quotation::where('project_id', $projectId)->exists();

        if ($hasDependencies) {
            $this->dispatch('toast', ['icon' => 'error', 'message' => 'No se puede eliminar: el proyecto tiene requisiciones, cotizaciones o gastos asociados.']);
            return;
        }

        $project->delete();
        $this->dispatch('toast', ['icon' => 'success', 'message' => 'Proyecto eliminado.']);
    }

    private function resetForm(): void
    {
        $this->name = '';
        $this->description = '';
        $this->client = '';
        $this->budget = '';
        $this->startDate = '';
        $this->endDate = '';
        $this->status = 'activo';
        $this->editingId = null;
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
