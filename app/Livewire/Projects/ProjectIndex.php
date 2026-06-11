<?php

namespace App\Livewire\Projects;

use App\Livewire\Concerns\EnforcesPermissions;
use App\Livewire\Concerns\WithSorting;
use App\Models\Expense;
use App\Models\Project;
use App\Models\Quotation;
use App\Models\Requisition;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

class ProjectIndex extends Component
{
    use EnforcesPermissions, WithPagination, WithSorting;

    public string $search = '';

    public string $statusFilter = '';

    public string $periodFilter = '';

    public array $selectedRows = [];

    public bool $allSelected = false;

    public bool $showModal = false;

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
        $this->selectedRows = [];
        $this->allSelected = false;
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
        $this->selectedRows = [];
        $this->allSelected = false;
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    #[On('edit-project')]
    public function handleEditProject(int $id): void
    {
        $this->openEditModal($id);
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
        $this->showModal = true;
    }

    public function saveProject(): void
    {
        if ($this->editingId) {
            if ($this->denyUnless('proyectos.editar', 'No tienes permiso para editar proyectos.')) {
                return;
            }

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
            $message = 'Proyecto actualizado correctamente.';
        } else {
            if ($this->denyUnless('proyectos.crear', 'No tienes permiso para crear proyectos.')) {
                return;
            }

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
            $message = 'Proyecto creado exitosamente.';
        }

        $this->showModal = false;
        $this->resetForm();
        $this->dispatch('toast', ['icon' => 'success', 'message' => $message]);
    }

    public function deleteProject(int $projectId): void
    {
        if ($this->denyUnless('proyectos.eliminar', 'No tienes permiso para eliminar proyectos.')) {
            return;
        }

        $project = Project::findOrFail($projectId);

        $hasDependencies = Requisition::where('project_id', $projectId)->exists() ||
            Expense::where('project_id', $projectId)->exists() ||
            Quotation::where('project_id', $projectId)->exists();

        if ($hasDependencies) {
            $this->dispatch('toast', ['icon' => 'error', 'message' => 'No se puede eliminar: el proyecto tiene requisiciones, cotizaciones o gastos asociados.']);

            return;
        }

        $project->delete();
        $this->dispatch('toast', ['icon' => 'success', 'message' => 'Proyecto eliminado.']);
        $this->selectedRows = array_diff($this->selectedRows, [$projectId]);
    }

    public function toggleAll($projectIds): void
    {
        if ($this->allSelected) {
            $this->selectedRows = array_merge($this->selectedRows, $projectIds);
            $this->selectedRows = array_unique($this->selectedRows);
        } else {
            $this->selectedRows = array_diff($this->selectedRows, $projectIds);
        }
    }

    public function bulkDelete(): void
    {
        if ($this->denyUnless('proyectos.eliminar', 'No tienes permiso para eliminar proyectos.')) {
            return;
        }

        if (empty($this->selectedRows)) {
            return;
        }

        // Check for dependencies
        $usedInRequisitions = Requisition::whereIn('project_id', $this->selectedRows)->pluck('project_id')->toArray();
        $usedInExpenses = Expense::whereIn('project_id', $this->selectedRows)->pluck('project_id')->toArray();
        $usedInQuotations = Quotation::whereIn('project_id', $this->selectedRows)->pluck('project_id')->toArray();

        $usedProjects = array_unique(array_merge($usedInRequisitions, $usedInExpenses, $usedInQuotations));
        $projectsToDelete = array_diff($this->selectedRows, $usedProjects);

        if (count($usedProjects) > 0) {
            $this->dispatch('toast', ['icon' => 'warning', 'message' => 'Algunos proyectos no pudieron ser eliminados porque tienen dependencias.']);
        }

        Project::whereIn('id', $projectsToDelete)->delete();

        if (count($projectsToDelete) > 0) {
            $this->dispatch('toast', ['icon' => 'success', 'message' => count($projectsToDelete) . ' proyecto(s) eliminado(s) exitosamente.']);
        }

        $this->selectedRows = [];
        $this->allSelected = false;
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
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('client', 'like', "%{$this->search}%"))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->periodFilter, function ($q) {
                $now = now();
                match ($this->periodFilter) {
                    'this_month' => $q->whereMonth('created_at', $now->month)->whereYear('created_at', $now->year),
                    'last_month' => $q->whereMonth('created_at', $now->subMonth()->month)->whereYear('created_at', $now->subMonth()->year),
                    'this_quarter' => $q->whereRaw('QUARTER(created_at) = ?', [$now->quarter])->whereYear('created_at', $now->year),
                    'this_year' => $q->whereYear('created_at', $now->year),
                    default => $q
                };
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(12);

        return view('livewire.projects.project-index', compact('projects'));
    }
}
