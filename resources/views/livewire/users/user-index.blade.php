<div x-data="{ showFilters: false }">
    {{-- Header --}}
    <x-page-header subtitle="Administración" title="Usuarios">
        <x-slot:actions>
            @if(auth()->user()->hasPermission('usuarios.crear'))
                <button wire:click="openCreateModal" class="btn-primary">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    Nuevo Usuario
                </button>
            @endif
        </x-slot:actions>
    </x-page-header>

    {{-- Filters Bar --}}
    <div class="flex flex-col sm:flex-row gap-3 mb-4 items-start sm:items-center">
        {{-- Search: compact width instead of full flex --}}
        <div class="relative w-full sm:w-72" x-data="{ focused: false }">
            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-text-muted"></i>
            <input wire:model.live.debounce.50ms="search" type="search" placeholder="Buscar por nombre o correo..."
                class="input pl-10 pr-10 w-full" @focus="focused = true" @blur="focused = false">
            <button x-show="$wire.search" x-transition @click="$wire.search = ''" type="button"
                class="absolute right-3 top-1/2 -translate-y-1/2 p-0.5 rounded hover:bg-surface-hover text-text-muted">
                <i data-lucide="x" class="w-3.5 h-3.5"></i>
            </button>
        </div>

        {{-- Filters Toggle Button with counter badge --}}
        <button @click="showFilters = !showFilters" type="button" class="btn-secondary shrink-0"
            :class="{ 'bg-primary-50 border-primary-200 text-primary-700': showFilters || $wire.roleFilter }">
            <i data-lucide="sliders-horizontal" class="w-4 h-4"></i>
            Filtros
            @if($roleFilter)
                <span class="ml-1.5 px-1.5 py-0.5 bg-primary-600 text-white text-[10px] font-bold rounded-full">1</span>
            @endif
        </button>

        <div class="flex-1"></div>

        {{-- Clear button: only when filters active --}}
        @if($search || $roleFilter)
            <button wire:click="$set('search', ''); $set('roleFilter', '');" type="button"
                class="inline-flex items-center gap-1.5 px-3 py-2 text-small text-text-muted hover:text-text-primary transition-colors">
                <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                Limpiar
            </button>
        @endif
    </div>

    {{-- Expandable Filters Panel --}}
    <div x-show="showFilters" x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-2" class="mb-6">
        <div class="card !p-4">
            <div class="flex flex-col sm:flex-row gap-4 items-start sm:items-center flex-wrap">
                <div class="flex items-center gap-2">
                    <i data-lucide="filter" class="w-4 h-4 text-text-muted"></i>
                    <span class="text-small font-medium text-text-secondary">Filtrar por:</span>
                </div>
                <x-custom-select wire:model.live="roleFilter" :options="$roles->pluck('name', 'id')->toArray()"
                    placeholder="Todos los roles" class="w-full sm:w-56" />
                <p class="text-xs-fluid text-text-muted">Selecciona un rol para filtrar la lista de usuarios</p>
            </div>
        </div>
    </div>

    {{-- Users Table --}}
    <div class="table-container">
        @if($users->isNotEmpty())
            <table>
                <thead>
                    <tr>
                        <x-sortable-header field="name" label="Usuario" :sortField="$sortField"
                            :sortDirection="$sortDirection" />
                        <x-sortable-header field="email" label="Correo Electrónico" :sortField="$sortField"
                            :sortDirection="$sortDirection" />
                        <x-sortable-header field="role_id" label="Rol" :sortField="$sortField"
                            :sortDirection="$sortDirection" />
                        <x-sortable-header field="active" label="Estado" :sortField="$sortField"
                            :sortDirection="$sortDirection" />
                        <th class="actions">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                                <tr>
                                    <td>
                                        <div class="flex flex-col">
                                            <span class="text-sm font-semibold text-text-primary">{{ $user->name }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="text-sm text-text-secondary">{{ $user->email }}</span>
                                    </td>
                                    <td>
                                        @if($user->role)
                                            <x-dynamic-badge :value="$user->role->name" />
                                        @else
                                            <span class="text-text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        <button wire:click="toggleActive({{ $user->id }})"
                                            class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md text-xs-fluid font-medium transition-colors border focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-primary-500
                                                    {{ $user->active
                        ? 'bg-surface-main text-emerald-600 border-border hover:border-emerald-500/50 hover:bg-surface-hover'
                        : 'bg-surface-main text-danger border-border hover:border-danger/50 hover:bg-surface-hover' }}"
                                            title="{{ $user->active ? 'Clic para desactivar' : 'Clic para activar' }}"
                                            @if(auth()->id() === $user->id || !auth()->user()->hasPermission('usuarios.editar')) disabled
                                            @endif>

                                            <span
                                                class="w-1.5 h-1.5 rounded-full {{ $user->active ? 'bg-emerald-500' : 'bg-red-500' }}"></span>
                                            {{ $user->active ? 'Activo' : 'Inactivo' }}
                                        </button>
                                    </td>
                                    <td class="actions">
                                        <div class="flex items-center justify-end gap-1">
                                            @if(auth()->user()->hasPermission('usuarios.editar'))
                                                <button wire:click="openEditModal({{ $user->id }})" class="btn-icon-primary" title="Editar">
                                                    <i data-lucide="pencil" class="w-4 h-4"></i>
                                                </button>
                                            @endif

                                            @if(auth()->user()->hasPermission('usuarios.eliminar') && auth()->id() !== $user->id)
                                                <button wire:click="deleteUser({{ $user->id }})"
                                                    wire:confirm="¿Estás seguro de que deseas eliminar este usuario? Esta acción es irreversible."
                                                    class="btn-icon-danger" title="Eliminar">
                                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <x-empty-state icon="users" title="No se encontraron usuarios"
                message="No hay usuarios registrados con los filtros actuales." />
        @endif
    </div>

    <div class="mt-4">
        {{ $users->links() }}
    </div>

    {{-- Modal Unificado Crear/Editar Usuario --}}
    @if($showModal)
        <x-modal show="showModal" :title="$editingId ? 'Editar Usuario' : 'Nuevo Usuario'">
            <form wire:submit="saveUser" class="p-5 space-y-4">
                <x-form-field label="Nombre completo" required error="{{ $errors->first('name') }}">
                    <input wire:model="name" type="text" class="input" placeholder="Ej. Juan Pérez">
                </x-form-field>

                <x-form-field label="Correo electrónico" required error="{{ $errors->first('email') }}">
                    <input wire:model="email" type="email" class="input" placeholder="ejemplo@empresa.com">
                </x-form-field>

                <div class="grid grid-cols-2 gap-4">
                    <x-form-field :label="$editingId ? 'Nueva Contraseña' : 'Contraseña'" :required="!$editingId"
                        error="{{ $errors->first('password') }}">
                        <input wire:model="password" type="password" class="input"
                            placeholder="{{ $editingId ? 'Dejar en blanco para mantener actual' : 'Mínimo 6 caracteres' }}">
                    </x-form-field>
                    <x-form-field label="Rol asignado" required error="{{ $errors->first('role_id') }}">
                        <x-custom-select wire:model="role_id" :options="$roles->pluck('name', 'id')->toArray()"
                            placeholder="Selecciona un rol..." />
                    </x-form-field>
                </div>

                <div class="flex items-center mt-2">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" wire:model="active"
                            class="rounded border-border text-primary-600 focus:ring-primary-500"
                            @if(auth()->id() === $editingId) disabled @endif>
                        <span class="text-sm font-medium text-text-primary">Usuario activo</span>
                    </label>
                    @if(auth()->id() === $editingId)
                        <span class="text-xs text-text-muted ml-2">(No puedes desactivar tu propia cuenta)</span>
                    @endif
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-border">
                    <button type="button" wire:click="$set('showModal', false)" class="btn-secondary">Cancelar</button>
                    <x-submit-button
                        target="saveUser">{{ $editingId ? 'Guardar Cambios' : 'Crear Usuario' }}</x-submit-button>
                </div>
            </form>
        </x-modal>
    @endif
</div>