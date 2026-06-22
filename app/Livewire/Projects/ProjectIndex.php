<?php

namespace App\Livewire\Projects;

use App\DTOs\ProjectDTO;
use App\Livewire\Concerns\EnforcesPermissions;
use App\Livewire\Concerns\WithFilters;
use App\Livewire\Concerns\WithSorting;
use App\Models\Project;
use App\Repositories\ProjectRepository;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ProjectIndex extends Component
{
    use EnforcesPermissions, WithFilters, WithPagination, WithSorting;

    #[Url(history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $statusFilter = '';

    #[Url(history: true)]
    public string $periodFilter = '';

    #[Url(history: true)]
    public string $dateFrom = '';

    #[Url(history: true)]
    public string $dateTo = '';

    public array $selectedRows = [];

    public bool $allSelected = false;

    public bool $showModal = false;

    public ?int $editingId = null;

    // Campos del formulario (creación y edición comparten las mismas propiedades)
    public string $name = '';

    public string $description = '';

    public ?int $client_id = null;

    public string $budget = '';

    public string $startDate = '';

    public string $endDate = '';

    public string $status = 'activo';



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
        $this->client_id = $project->client_id;
        $this->budget = (string) $project->budget;
        $this->startDate = $project->start_date?->format('Y-m-d') ?? '';
        $this->endDate = $project->end_date?->format('Y-m-d') ?? '';
        $this->status = $project->status;
        $this->showModal = true;
    }

    public function saveProject(ProjectRepository $repository): void
    {
        if ($this->editingId) {
            if ($this->denyUnless('proyectos.editar', 'No tienes permiso para editar proyectos.')) {
                return;
            }

            $validated = $this->validate([
                'name' => 'required|min:3|max:255',
                'description' => 'nullable|max:1000',
                'client_id' => 'nullable|exists:clients,id',
                'budget' => 'required|numeric|min:0',
                'startDate' => 'nullable|date',
                'endDate' => 'nullable|date|after_or_equal:startDate',
                'status' => 'required|in:activo,en_pausa,completado,cancelado',
            ]);

            $dto = ProjectDTO::fromArray($validated);
            $repository->update($this->editingId, $dto);
            $message = 'Proyecto actualizado correctamente.';
        } else {
            if ($this->denyUnless('proyectos.crear', 'No tienes permiso para crear proyectos.')) {
                return;
            }

            $validated = $this->validate([
                'name' => 'required|min:3|max:255',
                'description' => 'nullable|max:1000',
                'client_id' => 'nullable|exists:clients,id',
                'budget' => 'required|numeric|min:0',
                'startDate' => 'nullable|date',
                'endDate' => 'nullable|date|after_or_equal:startDate',
            ]);

            $dto = ProjectDTO::fromArray($validated);
            $repository->create($dto);
            $message = 'Proyecto creado exitosamente.';
        }

        $this->showModal = false;
        $this->resetForm();
        $this->dispatch('toast', ['icon' => 'success', 'message' => $message]);
    }

    public function deleteProject(int $projectId, ProjectRepository $repository): void
    {
        if ($this->denyUnless('proyectos.eliminar', 'No tienes permiso para eliminar proyectos.')) {
            return;
        }

        if (!$repository->delete($projectId)) {
            $this->dispatch('toast', ['icon' => 'error', 'message' => 'No se puede eliminar: el proyecto tiene requisiciones, cotizaciones o gastos asociados.']);
            return;
        }

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

    public function bulkDelete(ProjectRepository $repository): void
    {
        if ($this->denyUnless('proyectos.eliminar', 'No tienes permiso para eliminar proyectos.')) {
            return;
        }

        if (empty($this->selectedRows)) {
            return;
        }

        $projectsToDelete = $repository->bulkDelete($this->selectedRows);
        $notDeletedCount = count($this->selectedRows) - count($projectsToDelete);

        if ($notDeletedCount > 0) {
            $this->dispatch('toast', ['icon' => 'warning', 'message' => "{$notDeletedCount} proyecto(s) no pudieron ser eliminados porque tienen dependencias."]);
        }

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
        $this->client_id = null;
        $this->budget = '';
        $this->startDate = '';
        $this->endDate = '';
        $this->status = 'activo';
        $this->editingId = null;
    }

    public function mount(): void
    {
        $this->sortField = 'start_date';
        $this->sortDirection = 'desc';
    }

    #[Layout('components.layouts.app')]
    #[Title('Proyectos')]
    public function render()
    {
        $projects = Project::query()
            ->with('client')
            ->when($this->search, function ($q) {
                $q->where(function ($query) {
                    $query->where('name', 'ilike', "%{$this->search}%")
                          ->orWhereHas('client', function($c) {
                              $c->where('name', 'ilike', "%{$this->search}%");
                          });
                });
            })
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->periodFilter, function ($q) {
                $now = now();
                if ($this->periodFilter === 'custom') {
                    if ($this->dateFrom) {
                        $q->where('created_at', '>=', $this->dateFrom . ' 00:00:00');
                    }
                    if ($this->dateTo) {
                        $q->where('created_at', '<=', $this->dateTo . ' 23:59:59');
                    }
                    return $q;
                }
                
                return match ($this->periodFilter) {
                    'this_month' => $q->whereMonth('created_at', $now->month)->whereYear('created_at', $now->year),
                    'last_month' => $q->whereMonth('created_at', $now->subMonth()->month)->whereYear('created_at', $now->subMonth()->year),
                    'this_quarter' => $q->whereBetween('created_at', [$now->copy()->firstOfQuarter(), $now->copy()->lastOfQuarter()]),
                    'this_year' => $q->whereYear('created_at', $now->year),
                    default => $q
                };
            })
            ->when(true, function ($q) {
                $dir = strtolower($this->sortDirection) === 'asc' ? 'asc' : 'desc';
                if ($this->sortField === 'status') {
                    $q->orderByRaw("CASE status WHEN 'activo' THEN 1 WHEN 'en_pausa' THEN 2 WHEN 'completado' THEN 3 WHEN 'cancelado' THEN 4 ELSE 5 END $dir");
                } elseif (in_array($this->sortField, ['start_date', 'created_at', 'end_date'])) {
                    $q->orderByRaw("\"{$this->sortField}\" $dir NULLS LAST");
                } else {
                    $q->orderBy($this->sortField, $dir);
                }
            })
            ->paginate(12);

        $clients = \App\Models\Client::where('active', true)->orderBy('name')->pluck('name', 'id')->toArray();

        return view('livewire.projects.project-index', compact('projects', 'clients'));
    }
}
