<div x-data="userIndex(@entangle('selectedRows'))" x-init="totalOnPageStatic = {{ $users->count() }}; init()" data-total-on-page="{{ $users->count() }}">
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

    {{-- Unified Datagrid Card Container --}}
    <div class="mt-4 mb-6 flex flex-col bg-transparent md:bg-surface-card md:border md:border-border md:rounded-lg">
        @php
            $activeCount = ($roleFilter ? 1 : 0) + ($statusFilter ? 1 : 0);
            $hasActiveFilters = !empty($search) || $activeCount > 0;
        @endphp

        @if($users->isNotEmpty() || $hasActiveFilters)
            {{-- Header Group (Search + Filters + Chips) --}}
            <div class="card md:rounded-t-lg md:bg-surface-card md:border-0 md:shadow-none mb-4 md:mb-0">
                {{-- Filters Bar --}}
                <div class="flex flex-row gap-3 items-center justify-between w-full p-4 md:px-6 md:py-4">
                    {{-- Search --}}
                    <div class="flex-1 min-w-0">
                        <x-search-input wire:model.live.debounce.300ms="search" placeholder="Buscar por nombre o correo..." />
                    </div>

                    {{-- Filters Popover --}}
                    <x-filters-popover :activeCount="$activeCount" :columns="1" @filters-opened="initFilters()">
                        <x-form-field label="Rol">
                            <x-custom-select x-model="filterRole" :options="$roles->pluck('name', 'id')->toArray()" placeholder="Todos los roles" />
                        </x-form-field>

                        <x-form-field label="Estado">
                            <x-custom-select x-model="filterStatus" :options="['active' => 'Activo', 'inactive' => 'Inactivo']" placeholder="Todos los estados" />
                        </x-form-field>

                        <x-slot name="footer">
                            <x-button type="button" @click="clearFilters()" variant="link-muted">
                                Limpiar filtros
                            </x-button>
                            <x-button type="button" @click="applyFilters(); open = false" variant="primary">
                                Aplicar Filtros
                            </x-button>
                        </x-slot>
                    </x-filters-popover>
                </div>

                {{-- Active Chips Row --}}
                @if($activeCount > 0)
                <div class="flex flex-wrap items-center gap-2 px-4 pb-4 md:px-6 md:pb-4 pt-0">
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
            </div> {{-- End Header Group --}}
        @endif

        <div class="relative">
            <div class="w-full">
                <x-card.table class="hidden md:block w-full">
                @if($users->isEmpty() && !$hasActiveFilters)
                    <div wire:loading.class="hidden" wire:target="search, roleFilter, statusFilter, previousPage, nextPage, gotoPage" class="p-8">
                        <x-empty-state icon="users" title="No se encontraron usuarios" message="No hay registros que coincidan con tu búsqueda." />
                    </div>
                @endif
                <table class="w-full table-fixed min-w-[1100px] {{ $users->isEmpty() && !$hasActiveFilters ? 'hidden' : '' }}"
                    @if($users->isEmpty())
                        wire:loading.class.remove="hidden" wire:target="search, roleFilter, statusFilter, previousPage, nextPage, gotoPage"
                    @endif
                >
                    <colgroup>
                        <col class="w-14">           {{-- Checkbox --}}
                        <col class="w-[40%]">        {{-- Usuario / Correo --}}
                        <col class="w-[15%]">        {{-- Rol --}}
                        <col class="w-[15%]">        {{-- Estado --}}
                        <col class="w-[15%]">        {{-- Fecha de Registro --}}
                        <col class="w-28">           {{-- Acciones --}}
                    </colgroup>
                    <thead class="bg-surface-th border-b border-border/40">
                            <tr>
                                <th class="actions text-center">
                                    <input type="checkbox"
                                        class="w-4 h-4 rounded-sm text-primary-600 focus:ring-primary-500 border-border bg-surface-card cursor-pointer"
                                        x-bind:checked="allSelected"
                                        x-on:change="toggleAll({{ json_encode($users->pluck('id')->toArray()) }})" />
                                </th>
                                <x-sortable-header field="name" label="Usuario / Correo" :sortField="$sortField"
                                    :sortDirection="$sortDirection" />
                                <x-sortable-header field="role_id" label="Rol" :sortField="$sortField"
                                    :sortDirection="$sortDirection" />
                                <x-sortable-header field="active" label="Estado" :sortField="$sortField"
                                    :sortDirection="$sortDirection" />
                                <x-sortable-header field="created_at" label="Fecha de Registro" :sortField="$sortField" :sortDirection="$sortDirection" />
                                <th class="actions">Acciones</th>
                            </tr>
                        </thead>
                        <tbody wire:loading.class="hidden" wire:target="search, roleFilter, statusFilter, previousPage, nextPage, gotoPage">
                            @if($users->isEmpty() && $hasActiveFilters)
                                <tr>
                                    <td colspan="6" class="p-8">
                                        <x-empty-state icon="search" title="No se encontraron usuarios" message="Intenta ajustar tus filtros de búsqueda." />
                                    </td>
                                </tr>
                            @else
                                @foreach($users as $user)
                                            <tr wire:key="user-row-{{ $user->id }}"
                                                class="group hover:bg-surface-hover transition-colors duration-150"
                                                :class="selectedRows.includes('{{ $user->id }}') ? 'bg-primary-50/50' : ''">
                                                <td class="actions text-center" @click.stop>
                                                    <x-table-checkbox x-model="selectedRows" value="{{ $user->id }}" />
                                                </td>
                                                <td class="max-w-0">
                                                    <p class="font-semibold text-text-primary truncate" title="{{ $user->name }}">{{ $user->name }}</p>
                                                    <p class="text-xs text-text-muted truncate" title="{{ $user->email }}">{{ $user->email }}</p>
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
                                                <td class="actions" @click.stop>
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
                                                                    <x-dropdown-link as="button" type="button" @click="$dispatch('confirm-action', { title: 'Confirmar Acción', description: '¿Eliminar este usuario? Esta acción no puede deshacerse.', confirmLabel: 'Eliminar', variant: 'danger', action: 'deleteUser', params: [{{ $user->id }}] })" danger="true" icon="trash-2">
                                                                        Eliminar
                                                                    </x-dropdown-link>
                                                                @endif
                                                            </x-slot>
                                                        </x-dropdown>
                                                    </div>
                                                </td>
                                            </tr>
                                @endforeach
                            @endif
                        </tbody>
                        <tbody wire:loading.class.remove="hidden" wire:target="search, roleFilter, statusFilter, previousPage, nextPage, gotoPage" class="hidden">
                            @for($i = 0; $i < 5; $i++)
                                <tr class="opacity-{{ 100 - ($i * 15) }}">
                                    <td class="actions text-center">
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
                                    <td class="actions">
                                        <div class="flex items-center justify-end">
                                            <x-skeleton class="w-8 h-8 rounded-md" />
                                        </div>
                                    </td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>
        </x-card.table>

        <div class="md:hidden flex flex-col gap-4 mt-2">
            {{-- Tarjetas Móviles (Mobile View) --}}
            <div class="flex flex-col">
                <div wire:loading.class="hidden" wire:target="search, roleFilter, statusFilter, previousPage, nextPage, gotoPage" class="flex flex-col gap-4">
                    @if($users->isNotEmpty())
                        @foreach($users as $user)
                            <div class="card p-4 flex flex-col gap-3 relative transition-colors shadow-sm"
                                 :class="selectedRows.includes('{{ $user->id }}') ? 'bg-primary-50/50 border-primary-300' : ''"
                                 wire:key="user-mobile-card-{{ $user->id }}">
                                 
                                {{-- Cabecera de la Fila --}}
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <x-table-checkbox x-model="selectedRows" value="{{ $user->id }}" />
                                        @if($user->avatar)
                                            <img src="{{ Storage::url($user->avatar) }}" alt="{{ $user->name }}" class="w-8 h-8 rounded-full object-cover border border-border shadow-sm shrink-0">
                                        @else
                                            <div class="w-8 h-8 rounded-full bg-primary-100 text-primary-700 border border-primary-200 flex items-center justify-center font-bold shadow-sm shrink-0 text-xs">
                                                {{ substr($user->name, 0, 1) }}
                                            </div>
                                        @endif
                                        <span class="font-bold text-text-primary text-base truncate">{{ $user->name }}</span>
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <x-status-badge :status="$user->active ? 'activo' : 'inactivo'" :map="['activo' => 'success', 'inactivo' => 'danger']" />
                                        
                                        <x-dropdown align="right" width="48">
                                            <x-slot name="trigger">
                                                <button class="p-1 rounded-md text-text-muted hover:bg-surface-hover hover:text-text-primary transition-colors focus:outline-none">
                                                    <x-lucide-more-vertical class="w-5 h-5" />
                                                </button>
                                            </x-slot>
                                            <x-slot name="content">
                                                @if(auth()->user()->hasPermission('usuarios.editar'))
                                                    <x-dropdown-link as="button" wire:click="openEditModal({{ $user->id }})" icon="pencil">Editar</x-dropdown-link>
                                                @endif
                                                @if(auth()->user()->hasPermission('usuarios.editar') && auth()->id() !== $user->id)
                                                    <x-dropdown-link as="button" wire:click="toggleActive({{ $user->id }})" icon="power">{{ $user->active ? 'Desactivar' : 'Activar' }}</x-dropdown-link>
                                                @endif
                                                @if(auth()->user()->hasPermission('usuarios.eliminar') && auth()->id() !== $user->id)
                                                    <x-dropdown-link as="button" type="button" @click="$dispatch('confirm-action', { title: 'Confirmar Acción', description: '¿Eliminar este usuario? Esta acción no puede deshacerse.', confirmLabel: 'Eliminar', variant: 'danger', action: 'deleteUser', params: [{{ $user->id }}] })" danger="true" icon="trash-2">Eliminar</x-dropdown-link>
                                                @endif
                                            </x-slot>
                                        </x-dropdown>
                                    </div>
                                </div>

                                {{-- Contenido Indentado --}}
                                <div class="pl-8 flex flex-col gap-3">
                                    {{-- Subtítulo --}}
                                    <div class="text-xs text-text-muted flex flex-wrap items-center gap-x-3 gap-y-1">
                                        <span class="flex items-center gap-1.5 truncate">
                                            <x-lucide-mail class="w-3.5 h-3.5 shrink-0" />
                                            <span class="truncate">{{ $user->email }}</span>
                                        </span>
                                        <span class="flex items-center gap-1.5">
                                            <x-lucide-calendar class="w-3.5 h-3.5 shrink-0" />
                                            <span>{{ $user->created_at->format('d/m/Y') }}</span>
                                        </span>
                                    </div>

                                    {{-- Datos Financieros / Detalles --}}
                                    <div class="grid grid-cols-2 gap-x-4 gap-y-3">
                                        <div class="col-span-2">
                                            <p class="text-[10px] text-text-muted uppercase font-semibold mb-0.5">Rol</p>
                                            @if($user->role)
                                                <x-dynamic-badge :value="$user->role->name" />
                                            @else
                                                <span class="text-text-muted">—</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @elseif($hasActiveFilters)
                        <div class="p-12">
                            <x-empty-state icon="search" title="No se encontraron usuarios" message="Intenta ajustar tus filtros de búsqueda." />
                        </div>
                    @else
                        <div class="p-12">
                            <x-empty-state icon="users" title="No se encontraron usuarios" message="No hay registros que coincidan con tu búsqueda." />
                        </div>
                    @endif
                </div>

                {{-- Skeletons Móviles --}}
                <div wire:loading.class.remove="hidden" wire:target="search, roleFilter, statusFilter, previousPage, nextPage, gotoPage" class="hidden flex flex-col gap-4 mt-2">
                    @for($i = 0; $i < 4; $i++)
                        <div class="card p-4 flex flex-col gap-3 relative transition-colors shadow-sm opacity-{{ 100 - ($i * 15) }}">
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-3 min-w-0">
                                    <x-skeleton class="w-4 h-4 rounded-sm shrink-0" />
                                    <x-skeleton class="w-8 h-8 rounded-full shrink-0" />
                                    <x-skeleton class="h-5 w-32 rounded" />
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <x-skeleton class="h-6 w-20 rounded-full shrink-0" />
                                    <x-skeleton class="w-7 h-7 rounded-md" />
                                </div>
                            </div>
                            <div class="pl-8 flex flex-col gap-3">
                                <div class="flex gap-3">
                                    <x-skeleton class="h-3 w-28 rounded" />
                                    <x-skeleton class="h-3 w-20 rounded" />
                                </div>
                                <div class="grid grid-cols-2 gap-x-4 gap-y-3">
                                    <div class="col-span-2">
                                        <x-skeleton class="h-2 w-12 mb-1.5 rounded" />
                                        <x-skeleton class="h-5 w-24 rounded-full" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endfor
                </div>
            </div>
        </div>

        </div>

        {{-- Bulk Actions Bar --}}
        @if(auth()->user()->hasPermission('usuarios.eliminar') || auth()->user()->hasPermission('*'))
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
        @endif
        {{-- Pagination Footer --}}
        @if($users->hasPages())
            <x-card.footer>
                {{ $users->links(data: ['scrollTo' => false]) }}
            </x-card.footer>
        @endif
    </div>

    {{-- Delete / Action Modals --}}
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
                    <x-button wire:click="$set('showModal', false)" variant="soft">Cancelar</x-button>
                    <x-button type="submit" variant="primary" target="saveUser">{{ $editingId ? 'Guardar Cambios' : 'Crear Usuario' }}</x-button>
                </div>
            </form>
        </x-modal>
    @endif
    <x-confirm-modal />
</div>