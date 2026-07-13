<?php

namespace App\Livewire\Users;

use App\Livewire\Concerns\EnforcesPermissions;
use App\Livewire\Concerns\WithFilters;
use App\Livewire\Concerns\WithSorting;
use App\DTOs\UserDTO;
use App\Models\Role;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class UserIndex extends Component
{
    use EnforcesPermissions, WithFilters, WithPagination, WithSorting, WithFileUploads;

    #[Url(history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $roleFilter = '';

    #[Url(history: true)]
    public string $statusFilter = '';

    #[Url(history: true)]
    public string $trashedFilter = '';

    public array $selectedRows = [];

    public bool $allSelected = false;

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $role_id = '';

    public bool $active = true;

    public ?string $currentAvatarUrl = null;

    public $photo = null;

    public bool $removePhoto = false;



    public function mount()
    {
        if (! auth()->user()->hasPermission('usuarios.ver')) {
            abort(403, 'No tienes permiso para ver usuarios.');
        }
    }

    public function openCreateModal(): void
    {
        if ($this->denyUnless('usuarios.crear', 'No tienes permiso para crear usuarios.')) {
            return;
        }
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEditModal(int $id): void
    {
        if ($this->denyUnless('usuarios.editar', 'No tienes permiso para editar usuarios.')) {
            return;
        }
        $user = User::findOrFail($id);
        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role_id = (string) $user->role_id;
        $this->active = $user->active;
        $this->password = '';
        $this->currentAvatarUrl = $user->avatar_url;
        $this->photo = null;
        $this->removePhoto = false;
        $this->showModal = true;
    }

    public function saveUser(): void
    {
        if ($this->editingId) {
            $this->updateUser();
        } else {
            $this->createUser();
        }
    }

    private function createUser(): void
    {
        if ($this->denyUnless('usuarios.crear', 'No tienes permiso para crear usuarios.')) {
            return;
        }

        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'role_id' => 'required|exists:roles,id',
            'active' => 'boolean',
            'photo' => 'nullable|image|max:2048',
        ]);

        $avatarPath = null;
        if ($this->removePhoto) {
            $avatarPath = '';
        } elseif ($this->photo) {
            $avatarPath = $this->photo->store('avatars', config('filesystems.default'));
        }

        $dto = new UserDTO(
            name: $this->name,
            email: $this->email,
            role_id: (int)$this->role_id,
            active: $this->active,
            password: $this->password,
            avatar: $avatarPath,
        );

        $user = app(UserRepository::class)->save($dto);

        $this->dispatch('profile-avatar-updated', userId: $user->id, avatarUrl: $user->avatar_url, initial: strtoupper(substr($user->name, 0, 1)));

        $this->showModal = false;
        $this->resetForm();
        $this->dispatch('toast', ['icon' => 'success', 'message' => 'Usuario creado exitosamente.']);
    }

    private function updateUser(): void
    {
        if ($this->denyUnless('usuarios.editar', 'No tienes permiso para editar usuarios.')) {
            return;
        }

        $this->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($this->editingId)],
            'password' => 'nullable|min:6',
            'role_id' => 'required|exists:roles,id',
            'active' => 'boolean',
            'photo' => 'nullable|image|max:2048',
        ]);

        $avatarPath = null;
        if ($this->removePhoto) {
            $avatarPath = '';
        } elseif ($this->photo) {
            $avatarPath = $this->photo->store('avatars', config('filesystems.default'));
        }

        $dto = new UserDTO(
            name: $this->name,
            email: $this->email,
            role_id: (int)$this->role_id,
            active: $this->active,
            password: $this->password ?: null,
            id: $this->editingId,
            avatar: $avatarPath,
        );

        $user = app(UserRepository::class)->save($dto);

        $this->dispatch('profile-avatar-updated', userId: $user->id, avatarUrl: $user->avatar_url, initial: strtoupper(substr($user->name, 0, 1)));

        $this->showModal = false;
        $this->resetForm();
        $this->dispatch('toast', ['icon' => 'success', 'message' => 'Usuario actualizado correctamente.']);
    }

    public function deleteUser(int $id): void
    {
        if ($this->denyUnless('usuarios.eliminar', 'No tienes permiso para eliminar usuarios.')) {
            return;
        }

        if (auth()->id() === $id) {
            $this->dispatch('toast', ['icon' => 'error', 'message' => 'No puedes eliminar tu propio usuario.']);

            return;
        }

        app(UserRepository::class)->delete($id);
        $this->dispatch('toast', ['icon' => 'success', 'message' => 'Usuario eliminado.']);
        $this->selectedRows = array_diff($this->selectedRows, [$id]);
    }

    public function restore(int $id): void
    {
        if ($this->denyUnless('usuarios.editar', 'No tienes permiso para restaurar usuarios.')) {
            return;
        }

        $user = User::onlyTrashed()->findOrFail($id);
        $user->restore();

        $this->dispatch('toast', ['icon' => 'success', 'message' => 'Usuario restaurado exitosamente.']);
    }

    public function forceDelete(int $id): void
    {
        if ($this->denyUnless('usuarios.eliminar', 'No tienes permiso para eliminar usuarios.')) {
            return;
        }

        if (auth()->id() === $id) {
            $this->dispatch('toast', ['icon' => 'error', 'message' => 'No puedes eliminar tu propio usuario.']);
            return;
        }

        $user = User::withTrashed()->findOrFail($id);
        $user->forceDelete();

        $this->selectedRows = array_diff($this->selectedRows, [$id]);
        $this->dispatch('toast', ['icon' => 'success', 'message' => 'Usuario eliminado definitivamente.']);
    }

    public function toggleAll($userIds): void
    {
        if ($this->allSelected) {
            $this->selectedRows = array_merge($this->selectedRows, $userIds);
            $this->selectedRows = array_unique($this->selectedRows);
        } else {
            $this->selectedRows = array_diff($this->selectedRows, $userIds);
        }
    }

    public function bulkDelete(): void
    {
        if ($this->denyUnless('usuarios.eliminar', 'No tienes permiso para eliminar usuarios.')) {
            return;
        }

        if (empty($this->selectedRows)) {
            return;
        }

        // Prevent deleting oneself
        $userIdsToDelete = array_diff($this->selectedRows, [auth()->id()]);

        if (count($userIdsToDelete) < count($this->selectedRows)) {
            $this->dispatch('toast', ['icon' => 'warning', 'message' => 'No puedes eliminar tu propio usuario.']);
        }

        if ($this->trashedFilter === 'trashed') {
            User::onlyTrashed()->whereIn('id', $userIdsToDelete)->forceDelete();
            if (count($userIdsToDelete) > 0) {
                $this->dispatch('toast', ['icon' => 'success', 'message' => count($userIdsToDelete) . ' usuario(s) eliminado(s) definitivamente.']);
            }
        } else {
            app(UserRepository::class)->bulkDelete($userIdsToDelete);
            if (count($userIdsToDelete) > 0) {
                $this->dispatch('toast', ['icon' => 'success', 'message' => count($userIdsToDelete) . ' usuario(s) eliminado(s) exitosamente.']);
            }
        }

        $this->selectedRows = [];
        $this->allSelected = false;
    }

    public function toggleActive(int $id): void
    {
        if ($this->denyUnless('usuarios.editar', 'No tienes permiso para editar usuarios.')) {
            return;
        }

        if (auth()->id() === $id) {
            $this->dispatch('toast', ['icon' => 'error', 'message' => 'No puedes desactivar tu propio usuario.']);

            return;
        }

        $user = app(UserRepository::class)->toggleActive($id);

        $status = $user->active ? 'activado' : 'desactivado';
        $this->dispatch('toast', ['icon' => 'success', 'message' => "Usuario {$status} correctamente."]);
    }

    private function resetForm(): void
    {
        $this->name = '';
        $this->email = '';
        $this->password = '';
        $this->role_id = '';
        $this->active = true;
        $this->editingId = null;
        $this->currentAvatarUrl = null;
        $this->photo = null;
        $this->removePhoto = false;
        $this->resetValidation();
    }

    #[Layout('components.layouts.app')]
    #[Title('Usuarios')]
    public function render()
    {
        $users = User::with('role')
            ->when($this->trashedFilter === 'trashed', fn ($q) => $q->onlyTrashed())
            ->when($this->trashedFilter === 'all', fn ($q) => $q->withTrashed())
            ->when($this->search, function ($q) {
                $q->where(function ($query) {
                    $query->where('name', 'ilike', "%{$this->search}%")
                          ->orWhere('email', 'ilike', "%{$this->search}%");
                });
            })
            ->when($this->roleFilter, fn ($q) => $q->where('role_id', $this->roleFilter))
            ->when($this->statusFilter, function ($q) {
                if ($this->statusFilter === 'active') {
                    $q->where('active', true);
                } elseif ($this->statusFilter === 'inactive') {
                    $q->where('active', false);
                }
            })
            ->when(true, function ($q) {
                $dir = strtolower($this->sortDirection) === 'asc' ? 'asc' : 'desc';
                if (in_array($this->sortField, ['role_id', 'role'])) {
                    $q->orderBy(\App\Models\Role::select('name')->whereColumn('roles.id', 'users.role_id'), $dir);
                } else {
                    $q->orderBy($this->sortField, $dir);
                }
            })
            ->paginate($this->perPage);

        $roles = Role::all();

        $statusOptions = [
            'active' => 'Activos',
            'inactive' => 'Inactivos',
        ];

        $trashedOptions = [
            'trashed' => 'En papelera',
            'all' => 'Todos (activos y eliminados)',
        ];

        return view('livewire.users.user-index', compact('users', 'roles', 'statusOptions', 'trashedOptions'));
    }
}
