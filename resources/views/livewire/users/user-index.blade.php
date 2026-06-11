
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
        {{-- Search --}}
        <x-search-input wire:model.live.debounce.300ms="search" placeholder="Buscar por nombre o correo..." />

        {{-- Filters Popover --}}
        @php
            $activeCount = $roleFilter ? 1 : 0;
        @endphp
        <x-filters-popover :activeCount="$activeCount" :columns="1">
            <x-form-field label="Rol">
                <x-custom-select wire:model.live="roleFilter" :options="$roles->pluck('name', 'id')->toArray()" placeholder="Todos los roles" />
                <p class="text-xs-fluid text-text-muted mt-1.5">Selecciona un rol para filtrar la lista</p>
            </x-form-field>

            <x-slot name="footer">
                <button type="button" wire:click="$set('roleFilter', '');" @click="open = false" class="text-small text-text-muted hover:text-text-primary transition-colors font-medium">
                    Limpiar filtros
                </button>
            </x-slot>
        </x-filters-popover>

    {{-- Users Table --}}
    <div class="relative min-h-[200px]">
        <div wire:loading.class="hidden" wire:target="search, roleFilter, previousPage, nextPage, gotoPage" class="w-full">
            <div class="table-container hidden md:block">
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
                                                    <span class="text-small font-semibold text-text-primary">{{ $user->name }}</span>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="text-small text-text-secondary">{{ $user->email }}</span>
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
                                                            @if(auth()->id() !== $user->id && auth()->user()->hasPermission('usuarios.editar')) hover:opacity-80 cursor-pointer @else cursor-not-allowed opacity-50 @endif"
                                                    title="{{ $user->active ? 'Clic para desactivar' : 'Clic para activar' }}"
                                                    aria-pressed="{{ $user->active ? 'true' : 'false' }}"
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
                                                            wire:confirm="¿Eliminar este usuario? Esta acción no puede deshacerse."
                                                            variant="icon-danger" title="Eliminar" icon="trash-2" />
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <x-empty-state
                        icon="{{ ($search || $roleFilter) ? 'search-x' : 'users' }}"
                        title="{{ ($search || $roleFilter) ? 'Sin resultados' : 'Aún no hay usuarios' }}"
                        message="{{ ($search || $roleFilter) ? 'No se encontraron usuarios con los filtros aplicados.' : 'Crea el primer usuario del sistema.' }}">
                        @if($search || $roleFilter)
                            <x-slot:actions>
                                <x-button
                                    wire:click="$set('search', ''); $set('roleFilter', '');"
                                    variant="secondary" icon="rotate-ccw">
                                    Limpiar filtros
                                </x-button>
                            </x-slot:actions>
                        @endif
                    </x-empty-state>
                @endif
            </div>

            {{-- Tarjetas Móviles (Mobile View) --}}
            @if($users->isNotEmpty())
            <div class="md:hidden flex flex-col gap-4 mt-2">
                @foreach($users as $user)
                    <div class="card p-4 flex flex-col gap-3 relative overflow-hidden transition-colors group">
                        
                        <div class="flex justify-between items-start gap-2">
                            <div class="min-w-0">
                                <span class="font-bold text-text-primary text-body truncate block">{{ $user->name }}</span>
                                <p class="text-xs-fluid text-text-secondary mt-1 truncate">{{ $user->email }}</p>
                            </div>
                            <div class="text-right shrink-0">
                                @if($user->role)
                                    <x-dynamic-badge :value="$user->role->name" />
                                @endif
                            </div>
                        </div>

                        <div class="flex items-center justify-between text-xs-fluid text-text-muted bg-surface-main p-3 rounded-xl border border-border/50">
                            <span class="font-medium text-text-secondary">Estado</span>
                            <button wire:click="toggleActive({{ $user->id }})"
                                class="badge border focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-primary-500
                                        {{ $user->active ? 'badge-success border-success-border' : 'badge-danger border-danger-border' }}
                                        @if(auth()->id() !== $user->id && auth()->user()->hasPermission('usuarios.editar')) hover:opacity-80 cursor-pointer @else cursor-not-allowed opacity-50 @endif"
                                title="{{ $user->active ? 'Clic para desactivar' : 'Clic para activar' }}"
                                aria-pressed="{{ $user->active ? 'true' : 'false' }}"
                                @if(auth()->id() === $user->id || !auth()->user()->hasPermission('usuarios.editar')) disabled @endif>
                                <span class="badge-dot"></span>
                                {{ $user->active ? 'Activo' : 'Inactivo' }}
                            </button>
                        </div>

                        <div class="flex justify-end gap-1 pt-3 border-t border-border/50 mt-1">
                            @if(auth()->user()->hasPermission('usuarios.editar'))
                                <x-button wire:click="openEditModal({{ $user->id }})" variant="icon-primary" title="Editar" icon="pencil" class="text-xs-fluid w-8 h-8" />
                            @endif

                            @if(auth()->user()->hasPermission('usuarios.eliminar') && auth()->id() !== $user->id)
                                <x-button wire:click="deleteUser({{ $user->id }})"
                                    wire:confirm="¿Eliminar este usuario? Esta acción no puede deshacerse."
                                    variant="icon-danger" title="Eliminar" icon="trash-2" class="text-xs-fluid w-8 h-8" />
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
            @endif
        </div>

        {{-- Skeleton Loader --}}
        <div wire:loading.class.remove="hidden" wire:target="search, roleFilter, previousPage, nextPage, gotoPage"
            class="hidden absolute inset-0 w-full z-10 bg-surface-main">
            <div class="table-container hidden md:block">
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
                                    <x-skeleton class="h-4  rounded w-32" />
                                </td>
                                <td>
                                    <x-skeleton class="h-4  rounded w-48" />
                                </td>
                                <td>
                                    <x-skeleton class="h-5  rounded w-24" />
                                </td>
                                <td>
                                    <x-skeleton class="h-6  rounded w-20" />
                                </td>
                                <td class="actions">
                                    <div class="flex items-center justify-end gap-1">
                                        <x-skeleton class="w-8 h-8  rounded" />
                                        <x-skeleton class="w-8 h-8  rounded" />
                                    </div>
                                </td>
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </div>

            {{-- Skeletons Móviles --}}
            <div class="md:hidden flex flex-col gap-4 mt-2">
                @for($i = 0; $i < 4; $i++)
                    <div class="card p-4 flex flex-col gap-3 relative overflow-hidden bg-surface-main">
                        <div class="flex justify-between items-start gap-2">
                            <div>
                                <x-skeleton class="h-5 w-32 rounded" />
                                <x-skeleton class="h-3 w-40 rounded mt-1.5" />
                            </div>
                            <x-skeleton class="h-5 w-20 rounded-full" />
                        </div>
                        <div class="flex justify-between items-center bg-surface-hover/50 p-3 rounded-xl border border-border/50">
                            <x-skeleton class="h-4 w-16 rounded" />
                            <x-skeleton class="h-6 w-20 rounded-full" />
                        </div>
                        <div class="flex justify-end gap-1 pt-3 border-t border-border/50 mt-1">
                            <x-skeleton class="h-8 w-8 rounded" />
                            <x-skeleton class="h-8 w-8 rounded" />
                        </div>
                    </div>
                @endfor
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
                        <span class="text-small font-medium text-text-primary">Usuario activo</span>
                    </label>
                    @if(auth()->id() === $editingId)
                        <span class="text-xs-fluid text-text-muted ml-2">(No puedes desactivar tu propia cuenta)</span>
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