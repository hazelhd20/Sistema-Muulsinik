
<div x-data="{ showFilters: false }">
    {{-- Header --}}
    <x-page-header subtitle="Administración" title="Usuarios">
        <x-slot:actions>
            @if(auth()->user()->hasPermission('usuarios.crear'))
                <x-button wire:click="openCreateModal" variant="primary" icon="plus">
                    Nuevo Usuario
                </x-button>
            @endif
        </x-slot:actions>
    </x-page-header>

    {{-- Filters Bar --}}
    <div class="flex flex-col sm:flex-row gap-3 mb-4 items-start sm:items-center">
        {{-- Search: compact width instead of full flex --}}
        <div class="relative w-full sm:w-72" x-data="{ focused: false }">
            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-text-muted"></i>
            <input wire:model.live.debounce.300ms="search" type="search" placeholder="Buscar por nombre o correo..."
                class="input pl-10 pr-10 w-full" @focus="focused = true" @blur="focused = false">
            <button x-show="$wire.search" x-transition @click="$wire.search = ''" type="button"
                class="absolute right-3 top-1/2 -translate-y-1/2 p-0.5 rounded hover:bg-surface-hover text-text-muted">
                <i data-lucide="x" class="w-3.5 h-3.5"></i>
            </button>
        </div>

        {{-- Filters Toggle Button with counter badge --}}
        <x-button @click="showFilters = !showFilters" variant="secondary" icon="sliders-horizontal" class="shrink-0"
            x-bind:class="{ 'bg-primary-50 border-primary-200 text-primary-700': showFilters || $wire.roleFilter }">
            Filtros
            @if($roleFilter)
                <span class="ml-1.5 px-1.5 py-0.5 bg-primary-600 text-white text-[10px] font-bold rounded-full">1</span>
            @endif
        </x-button>

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
    <div class="relative min-h-[200px]">
        <div wire:loading.class="hidden" wire:target="search, roleFilter, previousPage, nextPage, gotoPage" class="w-full">
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
                                                    class="badge border focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-primary-500
                                                            {{ $user->active ? 'badge-success border-success-border' : 'badge-danger border-danger-border' }}
                                                            @if(auth()->id() !== $user->id && auth()->user()->hasPermission('usuarios.editar')) hover:opacity-85 cursor-pointer @else cursor-default opacity-90 @endif"
                                                    title="{{ $user->active ? 'Clic para desactivar' : 'Clic para activar' }}"
                                                    @if(auth()->id() === $user->id || !auth()->user()->hasPermission('usuarios.editar')) disabled @endif>
                                                    <span class="badge-dot"></span>
                                                    {{ $user->active ? 'Activo' : 'Inactivo' }}
                                                </button>
                                            </td>
                                            <td class="actions">
                                                <div class="flex items-center justify-end gap-1">
                                                    @if(auth()->user()->hasPermission('usuarios.editar'))
                                                        <x-button wire:click="openEditModal({{ $user->id }})" variant="icon-primary" title="Editar" icon="pencil" />
                                                    @endif

                                                    @if(auth()->user()->hasPermission('usuarios.eliminar') && auth()->id() !== $user->id)
                                                        <x-button wire:click="deleteUser({{ $user->id }})"
                                                            wire:confirm="¿Estás seguro de que deseas eliminar este usuario? Esta acción es irreversible."
                                                            variant="icon-danger" title="Eliminar" icon="trash-2" />
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
        </div>

        {{-- Skeleton Loader --}}
        <div wire:loading.class.remove="hidden" wire:target="search, roleFilter, previousPage, nextPage, gotoPage"
            class="hidden absolute inset-0 w-full z-10 bg-surface-main">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Correo Electrónico</th>
                            <th>Rol</th>
                            <th>Estado</th>
                            <th class="actions">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @for($i = 0; $i < 5; $i++)
                            <tr>
                                <td>
                                    <div class="h-4 skeleton rounded w-32"></div>
                                </td>
                                <td>
                                    <div class="h-4 skeleton rounded w-48"></div>
                                </td>
                                <td>
                                    <div class="h-5 skeleton rounded-full w-24"></div>
                                </td>
                                <td>
                                    <div class="h-6 skeleton rounded-full w-20"></div>
                                </td>
                                <td class="actions">
                                    <div class="flex items-center justify-end gap-1">
                                        <div class="w-8 h-8 skeleton rounded"></div>
                                        <div class="w-8 h-8 skeleton rounded"></div>
                                    </div>
                                </td>
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </div>
        </div>
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
                            class="rounded border-border accent-primary-600 focus:ring-primary-500"
                            @if(auth()->id() === $editingId) disabled @endif>
                        <span class="text-sm font-medium text-text-primary">Usuario activo</span>
                    </label>
                    @if(auth()->id() === $editingId)
                        <span class="text-xs text-text-muted ml-2">(No puedes desactivar tu propia cuenta)</span>
                    @endif
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-border">
                    <x-button wire:click="$set('showModal', false)" variant="secondary">Cancelar</x-button>
                    <x-button type="submit" variant="primary" target="saveUser">{{ $editingId ? 'Guardar Cambios' : 'Crear Usuario' }}</x-button>
                </div>
            </form>
        </x-modal>
    @endif
</div>