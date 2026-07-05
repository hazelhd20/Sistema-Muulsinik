<?php

namespace App\Livewire\Clients;

use App\Livewire\Concerns\EnforcesPermissions;
use App\Livewire\Concerns\WithSorting;
use App\DTOs\ClientDTO;
use App\Models\Client;
use App\Models\Project;
use App\Models\QuickBudget;
use App\Repositories\ClientRepository;
use App\Services\DataNormalizerService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Validation\Rule;

use App\Livewire\Concerns\WithPerPagePagination;

class ClientIndex extends Component
{
    use EnforcesPermissions, WithPagination, WithSorting, WithPerPagePagination;

    #[Url(history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $activeFilter = '';

    #[Url(history: true)]
    public string $trashedFilter = '';

    public array $selectedRows = [];

    public bool $allSelected = false;

    public bool $showCreateModal = false;

    // Campos del cliente
    public string $name = '';
    public string $legal_name = '';
    public string $rfc = '';
    public string $email = '';
    public string $phone = '';
    public bool $active = true;

    public ?int $editingId = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
        $this->selectedRows = [];
        $this->allSelected = false;
    }

    public function updatedActiveFilter(): void
    {
        $this->resetPage();
        $this->selectedRows = [];
        $this->allSelected = false;
    }

    public function mount(): void
    {
        if (! auth()->user()?->hasPermission('catalogos.ver') && ! auth()->user()?->hasPermission('*')) {
            abort(403, 'No tienes permiso para acceder al catálogo de clientes.');
        }

        $this->sortField = 'name';
        $this->sortDirection = 'asc';
    }

    public function openCreateModal(): void
    {
        if ($this->denyUnless('catalogos.editar', 'No tienes permiso para crear clientes.')) {
            return;
        }
        $this->resetForm();
        $this->showCreateModal = true;
    }

    public function openEditModal(int $clientId): void
    {
        if ($this->denyUnless('catalogos.editar', 'No tienes permiso para editar clientes.')) {
            return;
        }
        $client = Client::findOrFail($clientId);
        $this->editingId = $client->id;
        $this->name = $client->name;
        $this->legal_name = $client->legal_name ?? '';
        $this->rfc = $client->rfc ?? '';
        $this->email = $client->email ?? '';
        $this->phone = $client->phone ?? '';
        $this->active = (bool) $client->active;

        $this->showCreateModal = true;
    }

    public function saveClient(): void
    {
        if ($this->denyUnless('catalogos.editar', 'No tienes permiso para guardar clientes.')) {
            return;
        }

        $this->validate([
            'name' => ['required', 'min:2', 'max:255', Rule::unique('clients', 'name')->ignore($this->editingId)],
            'legal_name' => 'nullable|string|max:255',
            'rfc' => 'nullable|string|max:15',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'active' => 'boolean',
        ]);

        $normalizer = app(DataNormalizerService::class);
        $normalizedName = $normalizer->normalizeTitleCase($this->name);

        $dto = new ClientDTO(
            name: $normalizedName,
            legal_name: $this->legal_name ?: null,
            rfc: strtoupper($this->rfc) ?: null,
            email: strtolower($this->email) ?: null,
            phone: $this->phone ?: null,
            active: $this->active,
            id: $this->editingId,
        );

        app(ClientRepository::class)->save($dto);

        if ($this->editingId) {
            $this->dispatch('toast', ['icon' => 'success', 'message' => 'Cliente actualizado correctamente.']);
        } else {
            $this->dispatch('toast', ['icon' => 'success', 'message' => 'Cliente registrado en el catálogo.']);
        }

        $this->showCreateModal = false;
        $this->resetForm();
    }

    public function deleteClient(int $clientId): void
    {
        if ($this->denyUnless('catalogos.eliminar', 'No tienes permiso para eliminar clientes.')) {
            return;
        }

        $client = Client::findOrFail($clientId);

        // Check dependencies
        $hasProjects = Project::where('client_id', $clientId)->exists();
        $hasBudgets = QuickBudget::where('client_id', $clientId)->exists();

        if ($hasProjects || $hasBudgets) {
            $this->dispatch('toast', ['icon' => 'error', 'message' => 'No se puede eliminar: el cliente tiene proyectos o cotizaciones asignadas.']);
            return;
        }

        app(ClientRepository::class)->delete($clientId);
        $this->dispatch('toast', ['icon' => 'success', 'message' => 'Cliente eliminado del catálogo.']);
        $this->selectedRows = array_diff($this->selectedRows, [$clientId]);
    }

    public function restore(int $clientId): void
    {
        if ($this->denyUnless('catalogos.editar', 'No tienes permiso para restaurar clientes.')) {
            return;
        }

        $client = Client::onlyTrashed()->findOrFail($clientId);
        $client->restore();

        $this->dispatch('toast', ['icon' => 'success', 'message' => 'Cliente restaurado exitosamente.']);
    }

    public function forceDelete(int $clientId): void
    {
        if ($this->denyUnless('catalogos.eliminar', 'No tienes permiso para eliminar clientes.')) {
            return;
        }

        $client = Client::withTrashed()->findOrFail($clientId);

        $hasProjects = Project::where('client_id', $clientId)->exists();
        $hasBudgets = QuickBudget::where('client_id', $clientId)->exists();

        if ($hasProjects || $hasBudgets) {
            $this->dispatch('toast', ['icon' => 'error', 'message' => 'No se puede eliminar definitivamente: el cliente tiene proyectos o cotizaciones asignadas.']);
            return;
        }

        $client->forceDelete();
        $this->selectedRows = array_diff($this->selectedRows, [$clientId]);
        $this->dispatch('toast', ['icon' => 'success', 'message' => 'Cliente eliminado definitivamente.']);
    }

    public function toggleActive(int $clientId): void
    {
        if ($this->denyUnless('catalogos.editar', 'No tienes permiso para modificar clientes.')) {
            return;
        }

        $client = app(ClientRepository::class)->toggleActive($clientId);

        $status = $client->active ? 'activado' : 'desactivado';
        $this->dispatch('toast', ['icon' => 'success', 'message' => "Cliente {$status} correctamente."]);
    }

    public function toggleAll($clientIds): void
    {
        if ($this->allSelected) {
            $this->selectedRows = array_merge($this->selectedRows, $clientIds);
            $this->selectedRows = array_unique($this->selectedRows);
        } else {
            $this->selectedRows = array_diff($this->selectedRows, $clientIds);
        }
    }

    public function bulkDelete(): void
    {
        if ($this->denyUnless('catalogos.eliminar', 'No tienes permiso para eliminar clientes.')) {
            return;
        }

        if (empty($this->selectedRows)) {
            return;
        }

        if ($this->trashedFilter === 'trashed') {
            $clientsToDelete = Client::onlyTrashed()->whereIn('id', $this->selectedRows)->get();
            $deletedCount = 0;
            $inUseCount = 0;

            foreach ($clientsToDelete as $client) {
                $hasProjects = Project::where('client_id', $client->id)->exists();
                $hasBudgets = QuickBudget::where('client_id', $client->id)->exists();
                if ($hasProjects || $hasBudgets) {
                    $inUseCount++;
                    continue;
                }
                $client->forceDelete();
                $deletedCount++;
            }

            if ($inUseCount > 0) {
                $this->dispatch('toast', ['icon' => 'warning', 'message' => "{$inUseCount} cliente(s) no se pudieron eliminar porque están en uso."]);
            }
            if ($deletedCount > 0) {
                $this->dispatch('toast', ['icon' => 'success', 'message' => "{$deletedCount} cliente(s) eliminado(s) definitivamente."]);
            }
        } else {
            $usedInProjects = Project::whereIn('client_id', $this->selectedRows)->pluck('client_id')->toArray();
            $usedInBudgets = QuickBudget::whereIn('client_id', $this->selectedRows)->pluck('client_id')->toArray();
            $usedClients = array_unique(array_merge($usedInProjects, $usedInBudgets));

            $clientsToDelete = array_diff($this->selectedRows, $usedClients);

            if (count($usedClients) > 0) {
                $this->dispatch('toast', ['icon' => 'warning', 'message' => 'Algunos clientes no pudieron ser eliminados porque tienen proyectos o cotizaciones.']);
            }

            if (count($clientsToDelete) > 0) {
                app(ClientRepository::class)->bulkDelete($clientsToDelete);
                $this->dispatch('toast', ['icon' => 'success', 'message' => count($clientsToDelete) . ' cliente(s) eliminado(s) exitosamente.']);
            }
        }

        $this->selectedRows = [];
        $this->allSelected = false;
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->legal_name = '';
        $this->rfc = '';
        $this->email = '';
        $this->phone = '';
        $this->active = true;
        $this->resetErrorBag();
    }

    #[Layout('components.layouts.app')]
    #[Title('Clientes')]
    public function render()
    {
        $clients = Client::query()
            ->when($this->trashedFilter === 'trashed', fn ($q) => $q->onlyTrashed())
            ->when($this->trashedFilter === 'all', fn ($q) => $q->withTrashed())
            ->when($this->search, function ($q) {
                $q->where(function($query) {
                    $query->where('name', 'ilike', "%{$this->search}%")
                      ->orWhere('rfc', 'ilike', "%{$this->search}%")
                      ->orWhere('email', 'ilike', "%{$this->search}%");
                });
            })
            ->when($this->activeFilter !== '', function ($q) {
                $q->where('active', $this->activeFilter === '1');
            })
            ->withCount('projects', 'quickBudgets')
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        $statusOptions = [
            '1' => 'Activos',
            '0' => 'Inactivos',
        ];

        $trashedOptions = [
            'trashed' => 'En papelera',
            'all' => 'Todos (activos y eliminados)',
        ];

        return view('livewire.clients.client-index', compact('clients', 'statusOptions', 'trashedOptions'));
    }
}
