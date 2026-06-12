<div x-data="userIndex(@entangle('selectedRows'))" x-init="totalOnPage = {{ $users->count() }}; init()">
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
    <div class="flex flex-col sm:flex-row gap-3 mb-4 items-start sm:items-center justify-between w-full">
        {{-- Search --}}
        <x-search-input wire:model.live.debounce.300ms="search" placeholder="Buscar por nombre o correo..." />

        {{-- Filters Popover --}}
        @php
            $activeCount = ($roleFilter ? 1 : 0) + ($statusFilter ? 1 : 0);
        @endphp
        <x-filters-popover :activeCount="$activeCount" :columns="1" @filters-opened="initFilters()">
            <x-form-field label="Rol">
                <x-custom-select x-model="filterRole" :options="$roles->pluck('name', 'id')->toArray()" placeholder="Todos los roles" />
            </x-form-field>

            <x-form-field label="Estado">
                <x-custom-select x-model="filterStatus" :options="['active' => 'Activo', 'inactive' => 'Inactivo']" placeholder="Todos los estados" />
            </x-form-field>

            <x-slot name="footer">
                <button type="button" @click="clearFilters()" class="text-small text-text-muted hover:text-text-primary transition-colors font-medium">
                    Limpiar filtros
                </button>
                <x-button type="button" @click="applyFilters(); open = false" variant="primary">
                    Aplicar Filtros
                </x-button>
            </x-slot>
        </x-filters-popover>
    </div>

    {{-- Active Chips Row --}}
    @if($activeCount > 0)
    <div class="flex flex-wrap items-center gap-2 mb-4">
        @if($roleFilter)
            <x-filter-chip label="Rol" :value="$roles->firstWhere('id', $roleFilter)?->name ?? 'Desconocido'" wire:click="$set('roleFilter', '')" />
        @endif
        @if($statusFilter)
            @php
                $statusNames = ['active' => 'Activo', 'inactive' => 'Inactivo'];
            @endphp
            <x-filter-chip label="Estado" :value="$statusNames[$statusFilter] ?? $statusFilter" wire:click="$set('statusFilter', '')" />
        @endif
    </div>
    @endif

    {{-- Users Table --}}
    <div class="relative min-h-[200px]">
        <div class="w-full">
            <div class="table-container hidden md:block">
                <table>
                    <thead class="bg-surface-main/50 border-b border-border">
                            <tr>
                                <th class="w-10 pl-4 pr-2 text-center">
                                    <input type="checkbox"
                                        class="w-4 h-4 rounded-sm text-primary-600 focus:ring-primary-500 border-border bg-surface-card cursor-pointer"
                                        x-bind:checked="allSelected"
                                        x-on:change="toggleAll([{{ $users->pluck('id')->join(',') }}])" />
                                </th>
                                <x-sortable-header field="name" label="Usuario" :sortField="$sortField"
                                    :sortDirection="$sortDirection" class="w-1/3 min-w-[200px]" />
                                <th class="w-32">Rol</th>
                                <th class="w-32 text-center">Estado</th>
                                <x-sortable-header field="created_at" label="Registro" :sortField="$sortField"
                                    :sortDirection="$sortDirection" class="w-32" />
                                <th class="w-1 whitespace-nowrap text-right pr-4">Acciones</th>
                            </tr>
                        </thead>
                        <tbody wire:loading.class="hidden" wire:target="search, roleFilter, statusFilter, previousPage, nextPage, gotoPage">
                            @if($users->isNotEmpty())
                                @foreach($users as $user)
                                            <tr wire:key="user-row-{{ $user->id }}"
                                                class="group hover:bg-surface-hover/80 transition-colors duration-150"
                                                :class="selectedRows.includes('{{ $user->id }}') ? 'bg-primary-50/50' : ''">
                                                <td class="pl-4 pr-2 text-center" @click.stop>
                                                    <x-table-checkbox x-model="selectedRows" value="{{ $user->id }}" />
                                                </td>
                                                <td>
                                                    <p class="font-semibold text-text-primary">{{ $user->name }}</p>
                                                    <p class="text-xs text-text-muted">{{ $user->email }}</p>
                                                </td>
                                                <td>
                                                    @if($user->role)
                                                        <x-dynamic-badge :value="$user->role->name" />
                                                    @else
                                                        <span class="text-text-muted">—</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <x-status-badge :status="$user->active ? 'activo' : 'inactivo'" :map="['activo' => 'success', 'inactivo' => 'danger']" />
                                                </td>
                                                <td class="text-body text-text-secondary">
                                                    {{ $user->created_at->format('d/m/Y') }}
                                                </td>
                                                <td class="w-1 whitespace-nowrap pr-4 py-3" @click.stop>
                                                    <div class="flex items-center justify-end">
                                                        <x-dropdown align="right" width="48">
                                                            <x-slot name="trigger">
                                                                <x-button variant="icon" icon="more-vertical" class="text-text-muted hover:text-text-primary" aria-label="Opciones" title="Opciones" />
                                                            </x-slot>

                                                            <x-slot name="content">
                                                                @if(auth()->user()->hasPermission('usuarios.editar'))
                                                                    <x-dropdown-link as="button" wire:click="openEditModal({{ $user->id }})" icon="pencil">
                                                                        Editar
                                                                    </x-dropdown-link>
                                                                @endif
                                                                
                                                                @if(auth()->user()->hasPermission('usuarios.editar') && auth()->id() !== $user->id)
                                                                    <x-dropdown-link as="button" wire:click="toggleActive({{ $user->id }})" icon="power">
                                                                        {{ $user->active ? 'Desactivar' : 'Activar' }}
                                                                    </x-dropdown-link>
                                                                @endif

                                                                @if(auth()->user()->hasPermission('usuarios.eliminar') && auth()->id() !== $user->id)
                                                                    <x-dropdown-link as="button" wire:click="deleteUser({{ $user->id }})"
                                                                        wire:confirm="¿Eliminar este usuario? Esta acción no puede deshacerse." danger="true" icon="trash-2">
                                                                        Eliminar
                                                                    </x-dropdown-link>
                                                                @endif
                                                            </x-slot>
                                                        </x-dropdown>
                                                    </div>
                                                </td>
                                            </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="6">
                                        <x-empty-state icon="users" title="No se encontraron usuarios"
                                            message="No hay registros que coincidan con tu búsqueda." />
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                        <tbody wire:loading.class.remove="hidden" wire:target="search, roleFilter, statusFilter, previousPage, nextPage, gotoPage" class="hidden">
                            @for($i = 0; $i < 5; $i++)
                                <tr class="opacity-{{ 100 - ($i * 15) }}">
                                    <td class="pl-4 pr-2 text-center">
                                        <x-skeleton class="w-4 h-4 rounded-sm mx-auto" />
                                    </td>
                                    <td>
                                        <x-skeleton class="h-4 rounded w-32 mb-1" />
                                        <x-skeleton class="h-3 rounded w-48" />
                                    </td>
                                    <td>
                                        <x-skeleton class="h-5 rounded w-24" />
                                    </td>
                                    <td>
                                        <x-skeleton class="h-6 rounded w-20" />
                                    </td>
                                    <td>
                                        <x-skeleton class="h-4 rounded w-20" />
                                    </td>
                                    <td class="w-1 whitespace-nowrap pr-4 py-3">
                                        <div class="flex items-center justify-end">
                                            <x-skeleton class="w-8 h-8 rounded-md" />
                                        </div>
                                    </td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>
            </div>

            {{-- Tarjetas Móviles (Mobile View) --}}
            <div class="md:hidden flex flex-col gap-4 mt-2">
                <div wire:loading.class="hidden" wire:target="search, roleFilter, statusFilter, previousPage, nextPage, gotoPage" class="flex flex-col gap-4">
                    @if($users->isNotEmpty())
                        @foreach($users as $user)
                            <div class="card p-4 flex flex-col gap-3 relative overflow-hidden transition-colors"
                                 :class="selectedRows.includes('{{ $user->id }}') ? 'bg-primary-50/50 border-primary-300' : ''"
                                 wire:key="user-mobile-card-{{ $user->id }}">
                                
                                <div class="flex justify-between items-start gap-2">
                                    <div class="flex items-start gap-3">
                                        <div class="pt-1">
                                            <x-table-checkbox x-model="selectedRows" value="{{ $user->id }}" />
                                        </div>
                                        <div class="shrink-0 pt-0.5">
                                            @if($user->avatar)
                                                <img src="{{ Storage::url($user->avatar) }}" alt="{{ $user->name }}" class="w-10 h-10 rounded-full object-cover border border-border shadow-sm">
                                            @else
                                                <div class="w-10 h-10 rounded-full bg-primary-100 text-primary-700 border border-primary-200 flex items-center justify-center font-bold shadow-sm">
                                                    {{ substr($user->name, 0, 1) }}
                                                </div>
                                            @endif
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <span class="font-bold text-text-primary text-body">{{ $user->name }}</span>
                                            </div>
                                            <p class="text-xs text-text-secondary mt-0.5 truncate">{{ $user->email }}</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-2 bg-surface-hover/50 p-3 rounded-xl border border-border/50 text-small">
                                    <div>
                                        <p class="text-text-muted font-medium text-[11px] uppercase tracking-wider mb-1">Rol</p>
                                        @if($user->role)
                                            <x-dynamic-badge :value="$user->role->name" />
                                        @else
                                            <span class="text-text-muted">—</span>
                                        @endif
                                    </div>
                                    <div class="flex flex-col items-end">
                                        <p class="text-text-muted font-medium text-[11px] uppercase tracking-wider mb-1 text-right">Estado</p>
                                        <x-status-badge :status="$user->active ? 'activo' : 'inactivo'" :map="['activo' => 'success', 'inactivo' => 'danger']" />
                                    </div>
                                    <div class="col-span-2 flex items-center justify-between mt-1 pt-2 border-t border-border/50">
                                        <div class="flex items-center gap-1.5 text-text-secondary">
                                            <x-lucide-calendar class="w-3.5 h-3.5 text-text-muted" />
                                            <span>Registro: {{ $user->created_at->format('d/m/Y') }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center justify-end pt-2 border-t border-border mt-1">
                                    <x-dropdown align="right" width="48">
                                        <x-slot name="trigger">
                                            <x-button variant="secondary" class="w-full justify-center">
                                                <x-lucide-more-horizontal class="w-4 h-4" />
                                                <span class="ml-2">Opciones</span>
                                            </x-button>
                                        </x-slot>

                                        <x-slot name="content">
                                            @if(auth()->user()->hasPermission('usuarios.editar'))
                                                <x-dropdown-link as="button" wire:click="openEditModal({{ $user->id }})" icon="pencil">
                                                    Editar
                                                </x-dropdown-link>
                                            @endif
                                            
                                            @if(auth()->user()->hasPermission('usuarios.editar') && auth()->id() !== $user->id)
                                                <x-dropdown-link as="button" wire:click="toggleActive({{ $user->id }})" icon="power">
                                                    {{ $user->active ? 'Desactivar' : 'Activar' }}
                                                </x-dropdown-link>
                                            @endif
                                            @if(auth()->user()->hasPermission('usuarios.eliminar') && auth()->id() !== $user->id)
                                                <x-dropdown-link as="button" wire:click="deleteUser({{ $user->id }})"
                                                    wire:confirm="¿Eliminar este usuario? Esta acción no puede deshacerse." danger="true" icon="trash-2">
                                                    Eliminar
                                                </x-dropdown-link>
                                            @endif
                                        </x-slot>
                                    </x-dropdown>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <x-empty-state icon="users" title="No se encontraron usuarios" message="No hay registros que coincidan con tu búsqueda." />
                    @endif
                </div>

                {{-- Skeletons Móviles --}}
                <div wire:loading.class.remove="hidden" wire:target="search, roleFilter, statusFilter, previousPage, nextPage, gotoPage" class="hidden flex flex-col gap-4">
                    @for($i = 0; $i < 4; $i++)
                        <div class="card p-4 flex flex-col gap-3 relative overflow-hidden bg-surface-main opacity-{{ 100 - ($i * 15) }}">
                            <div class="flex justify-between items-start gap-2">
                                <div class="flex items-start gap-3">
                                    <div class="pt-1"><x-skeleton class="w-4 h-4 rounded-sm" /></div>
                                    <x-skeleton class="w-10 h-10 rounded-full" />
                                    <div class="min-w-0 flex-1">
                                        <x-skeleton class="h-5 w-32 rounded mb-1.5" />
                                        <x-skeleton class="h-3 w-40 rounded" />
                                    </div>
                                </div>
                            </div>
                            <div class="flex justify-between items-center bg-surface-hover/50 p-3 rounded-xl border border-border/50">
                                <x-skeleton class="h-4 w-16 rounded" />
                                <x-skeleton class="h-6 w-20 rounded-full" />
                            </div>
                            <div class="flex justify-end pt-2 border-t border-border mt-1">
                                <x-skeleton class="h-9 w-full rounded-md" />
                            </div>
                        </div>
                    @endfor
                </div>
            </div>
        </div>
        
        {{-- Bulk Actions Bar --}}
        <x-bulk-actions-bar>
            <x-button
                @click="$dispatch('confirm-action', {
                    title: 'Eliminar Usuarios',
                    description: 'Se eliminarán permanentemente los usuarios seleccionados (excepto el tuyo propio).',
                    confirmLabel: 'Eliminar',
                    variant: 'danger',
                    action: 'bulkDelete',
                    params: []
                })"
                variant="danger"
                icon="trash-2">
                Eliminar
            </x-button>
        </x-bulk-actions-bar>

    </div>
    
    {{-- Delete / Action Modals --}}
    <x-confirm-modal />

    <div class="mt-4">{{ $users->links() }}</div>

    {{-- Modal Unificado Crear/Editar Usuario --}}
    @if($showModal)
        <x-modal show="showModal" :title="$editingId ? 'Editar Usuario' : 'Nuevo Usuario'">
            <form wire:submit="saveUser" class="p-5 space-y-4" autocomplete="off">
                <x-form-field label="Nombre completo" required error="{{ $errors->first('name') }}">
                    <input wire:model="name" type="text" class="input" placeholder="Ej. Juan Pérez" autocomplete="off">
                </x-form-field>

                <x-form-field label="Correo electrónico" required error="{{ $errors->first('email') }}">
                    <input wire:model="email" type="email" class="input" placeholder="ejemplo@empresa.com" autocomplete="off">
                </x-form-field>

                <div class="grid grid-cols-2 gap-4">
                    <x-form-field :label="$editingId ? 'Nueva Contraseña' : 'Contraseña'" :required="!$editingId"
                        error="{{ $errors->first('password') }}">
                        <input wire:model="password" type="password" class="input"
                            placeholder="{{ $editingId ? 'Dejar en blanco para mantener actual' : 'Mínimo 6 caracteres' }}"
                            autocomplete="new-password">
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