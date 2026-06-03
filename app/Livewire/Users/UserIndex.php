<?php

namespace App\Livewire\Users;

use App\Livewire\Concerns\EnforcesPermissions;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use App\Livewire\Concerns\WithSorting;

class UserIndex extends Component
{
    use WithPagination, EnforcesPermissions, WithSorting;

    public string $search = '';
    public string $roleFilter = '';

    public bool $showCreateModal = false;
    public bool $showEditModal = false;
    public ?int $editingId = null;

    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $role_id = '';
    public bool $active = true;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function mount()
    {
        if (!auth()->user()->hasPermission('usuarios.ver')) {
            abort(403, 'No tienes permiso para ver usuarios.');
        }
    }

    public function openCreateModal(): void
    {
        if ($this->denyUnless('usuarios.crear', 'No tienes permiso para crear usuarios.'))
            return;
        $this->resetForm();
        $this->showCreateModal = true;
    }

    public function openEditModal(int $id): void
    {
        if ($this->denyUnless('usuarios.editar', 'No tienes permiso para editar usuarios.'))
            return;
        $user = User::findOrFail($id);
        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role_id = (string) $user->role_id;
        $this->active = $user->active;
        $this->password = '';
        $this->showEditModal = true;
    }

    public function createUser(): void
    {
        if ($this->denyUnless('usuarios.crear', 'No tienes permiso para crear usuarios.'))
            return;

        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'role_id' => 'required|exists:roles,id',
            'active' => 'boolean',
        ]);

        User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($this->password),
            'role_id' => $this->role_id,
            'active' => $this->active,
        ]);

        $this->showCreateModal = false;
        $this->resetForm();
        $this->dispatch('toast', ['icon' => 'success', 'message' => 'Usuario creado exitosamente.']);
    }

    public function updateUser(): void
    {
        if ($this->denyUnless('usuarios.editar', 'No tienes permiso para editar usuarios.'))
            return;

        $this->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($this->editingId)],
            'password' => 'nullable|min:6',
            'role_id' => 'required|exists:roles,id',
            'active' => 'boolean',
        ]);

        $user = User::findOrFail($this->editingId);
        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'role_id' => $this->role_id,
            'active' => $this->active,
        ];

        if (!empty($this->password)) {
            $data['password'] = Hash::make($this->password);
        }

        $user->update($data);

        $this->showEditModal = false;
        $this->resetForm();
        $this->dispatch('toast', ['icon' => 'success', 'message' => 'Usuario actualizado correctamente.']);
    }

    public function deleteUser(int $id): void
    {
        if ($this->denyUnless('usuarios.eliminar', 'No tienes permiso para eliminar usuarios.'))
            return;

        if (auth()->id() === $id) {
            $this->dispatch('toast', ['icon' => 'error', 'message' => 'No puedes eliminar tu propio usuario.']);
            return;
        }

        User::findOrFail($id)->delete();
        $this->dispatch('toast', ['icon' => 'success', 'message' => 'Usuario eliminado.']);
    }

    public function toggleActive(int $id): void
    {
        if ($this->denyUnless('usuarios.editar', 'No tienes permiso para editar usuarios.'))
            return;

        if (auth()->id() === $id) {
            $this->dispatch('toast', ['icon' => 'error', 'message' => 'No puedes desactivar tu propio usuario.']);
            return;
        }

        $user = User::findOrFail($id);
        $user->update(['active' => !$user->active]);

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
        $this->resetValidation();
    }

    #[Layout('components.layouts.app')]
    #[Title('Usuarios')]
    public function render()
    {
        $users = User::with('role')
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%"))
            ->when($this->roleFilter, fn($q) => $q->where('role_id', $this->roleFilter))
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(10);

        $roles = Role::all();

        return view('livewire.users.user-index', compact('users', 'roles'));
    }
}
