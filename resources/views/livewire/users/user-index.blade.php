<div x-data="userIndex(@entangle('selectedRows'))" x-init="totalOnPageStatic = {{ $users->count() }}; init()" data-total-on-page="{{ $users->count() }}">
    {{-- Header --}}
    <x-page-header subtitle="Administración" title="Usuarios">
        <x-slot:actions>
            @if(auth()->user()->hasPermission('usuarios.crear'))
                <x-button wire:click="openCreateModal" variant="primary" icon="plus" class="w-full sm:w-auto justify-center">
                    Nuevo Usuario
                </x-button>
            @endif
        </x-slot:actions>
    </x-page-header>

    {{-- Unified Datagrid Card Container --}}
    <div class="mt-0 flex flex-col bg-transparent md:bg-surface-card md:border md:border-border md:rounded-xl">
        @php
            $activeCount = ($roleFilter ? 1 : 0) + ($statusFilter ? 1 : 0) + ($trashedFilter ? 1 : 0);
            $hasActiveFilters = !empty($search) || $activeCount > 0;
        @endphp

        @if($users->isNotEmpty() || $hasActiveFilters)
            {{-- Header Group (Search + Filters + Chips) --}}
            <div class="bg-transparent border-0 shadow-none md:card md:rounded-t-xl md:bg-surface-card md:border-0 md:shadow-none mb-4 md:mb-0">
                {{-- Filters Bar --}}
                <div class="flex flex-row gap-2.5 items-center justify-between w-full py-1 md:px-6 md:py-4">
                    {{-- Search --}}
                    <div class="flex-1 min-w-0">
                        <x-search-input wire:model.live.debounce.300ms="search" placeholder="Buscar por nombre o correo..." />
                    </div>

                    {{-- Filters Popover --}}
                    <x-filters-popover :activeCount="$activeCount" :columns="2" @filters-opened="initFilters()">
                        <x-form-field label="Rol">
                            <x-custom-select x-model="filterRole" :options="$roles->pluck('name', 'id')->toArray()" placeholder="Todos los roles" />
                        </x-form-field>

                        <x-form-field label="Estado">
                            <x-custom-select x-model="filterStatus" :options="$statusOptions" placeholder="Todos los estados" />
                        </x-form-field>

                        <x-form-field label="Estado / papelera">
                            <x-custom-select x-model="filterTrashed" :options="$trashedOptions"
                                placeholder="Activos (por defecto)" />
                        </x-form-field>

                        <x-slot name="footer">
                            <x-button type="button" @click="clearFilters()" variant="link-muted">
                                Limpiar filtros
                            </x-button>
                            <x-button type="button" @click="applyFilters(); open = false" variant="primary">
                                Aplicar filtros
                            </x-button>
                        </x-slot>
                    </x-filters-popover>
                </div>

                {{-- Active Chips Row --}}
                @if($activeCount > 0)
                <div class="flex flex-wrap items-center gap-2 pb-3 md:px-6 md:pb-4 pt-1">
                    @if($roleFilter)
                        <x-filter-chip label="Rol" :value="$roles->firstWhere('id', $roleFilter)?->name ?? 'Desconocido'" wire:click="$set('roleFilter', '')" />
                    @endif
                    @if($statusFilter)
                        <x-filter-chip label="Estado" :value="$statusOptions[$statusFilter] ?? $statusFilter" wire:click="$set('statusFilter', '')" />
                    @endif
                    @if($trashedFilter)
                        <x-filter-chip label="Papelera" :value="$trashedOptions[$trashedFilter] ?? $trashedFilter" wire:click="$set('trashedFilter', '')" />
                    @endif
                </div>
                @endif
            </div> {{-- End Header Group --}}
        @endif

        <div class="relative">
            <div class="w-full">
                <x-card.table class="hidden md:block w-full">
                @if($users->isEmpty() && !$hasActiveFilters)
                    <div wire:loading.class="hidden" wire:target="search, roleFilter, statusFilter, trashedFilter, previousPage, nextPage, gotoPage" class="p-8">
                        <x-empty-state icon="users" title="No se encontraron usuarios" message="No hay registros que coincidan con tu búsqueda." />
                    </div>
                @endif
                <table class="w-full table-fixed min-w-[1100px] {{ $users->isEmpty() && !$hasActiveFilters ? 'hidden' : '' }}"
                    @if($users->isEmpty())
                        wire:loading.class.remove="hidden" wire:target="search, roleFilter, statusFilter, trashedFilter, previousPage, nextPage, gotoPage"
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
                                <th class="actions pl-6 pr-2 text-left">
                                    <x-table-checkbox x-bind:checked="allSelected"
                                        @change="toggleAll({{ json_encode($users->pluck('id')->toArray()) }})" />
                                </th>
                                <x-sortable-header field="name" label="Usuario / Correo" :sortField="$sortField"
                                    :sortDirection="$sortDirection" />
                                <x-sortable-header field="role_id" label="Rol" :sortField="$sortField"
                                    :sortDirection="$sortDirection" />
                                <x-sortable-header field="active" label="Estado" :sortField="$sortField"
                                    :sortDirection="$sortDirection" />
                                <x-sortable-header field="created_at" label="Fecha de Registro" :sortField="$sortField" :sortDirection="$sortDirection" />
                                <th class="actions pr-6 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody wire:loading.class="hidden" wire:target="search, roleFilter, statusFilter, trashedFilter, previousPage, nextPage, gotoPage">
                            @if($users->isEmpty() && $hasActiveFilters)
                                <tr>
                                    <td colspan="6" class="p-8">
                                        <x-empty-state icon="search" title="No se encontraron usuarios" message="Intenta ajustar tus filtros de búsqueda." />
                                    </td>
                                </tr>
                            @else
                                @foreach($users as $user)
                                            <tr wire:key="user-row-{{ $user->id }}"
                                                class="group hover:bg-surface-hover transition-colors duration-150 {{ $user->trashed() ? 'opacity-70 bg-danger-50/10' : '' }}"
                                                :class="selectedRows.includes('{{ $user->id }}') ? 'bg-primary-50/50' : ''">
                                                <td class="actions pl-6 pr-2 text-left" @click.stop="$event.stopPropagation()">
                                                    <x-table-checkbox x-model="selectedRows" value="{{ $user->id }}" />
                                                </td>
                                                <td class="max-w-0">
                                                    <div class="flex items-center gap-3">
                                                        @if($user->avatar_url)
                                                            <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="w-8 h-8 rounded-full object-cover shadow-sm shrink-0">
                                                        @else
                                                            <div class="w-8 h-8 rounded-full bg-primary-600 dark:bg-primary-500 text-white flex items-center justify-center shrink-0 select-none shadow-sm">
                                                                <span class="text-xs-fluid font-bold leading-none inline-flex items-center justify-center">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                                                            </div>
                                                        @endif
                                                        <div class="min-w-0 flex-1">
                                                            <div class="flex items-center gap-2">
                                                                <p class="text-body font-bold text-text-primary truncate" title="{{ $user->name }}">{{ $user->name }}</p>
                                                                @if($user->trashed())
                                                                    <x-badge variant="danger" size="sm">Eliminado</x-badge>
                                                                @endif
                                                            </div>
                                                            <p class="text-xs-fluid text-text-muted truncate" title="{{ $user->email }}">{{ $user->email }}</p>
                                                        </div>
                                                    </div>
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
                                                <td class="text-body font-medium text-text-secondary">
                                                    {{ $user->created_at->format('d/m/Y') }}
                                                </td>
                                                <td class="actions pr-6 py-3" @click.stop="$event.stopPropagation()">
                                                    <div class="flex items-center justify-end">
                                                        <x-dropdown align="right" width="48">
                                                            <x-slot name="trigger">
                                                                <x-button variant="icon" icon="more-vertical" aria-label="Opciones" title="Opciones" />
                                                            </x-slot>

                                                            <x-slot name="content">
                                                                @if($user->trashed())
                                                                    @if(auth()->user()->hasPermission('usuarios.editar'))
                                                                        <x-dropdown-link as="button" wire:click="restore({{ $user->id }})" icon="rotate-ccw">
                                                                            Restaurar
                                                                        </x-dropdown-link>
                                                                    @endif
                                                                    @if(auth()->user()->hasPermission('usuarios.eliminar') && auth()->id() !== $user->id)
                                                                        <x-dropdown-link as="button" type="button" @click="$dispatch('confirm-action', { title: 'Eliminar Definitivamente', description: '¿Eliminar permanentemente este usuario? Esta acción destruirá el registro.', confirmLabel: 'Eliminar Definitivamente', variant: 'danger', action: 'forceDelete', params: [{{ $user->id }}] })" danger="true" icon="trash-2">
                                                                            Eliminar Definitivamente
                                                                        </x-dropdown-link>
                                                                    @endif
                                                                @else
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
                                                                @endif
                                                            </x-slot>
                                                        </x-dropdown>
                                                    </div>
                                                </td>
                                            </tr>
                                @endforeach
                            @endif
                        </tbody>
                        <tbody wire:loading.class.remove="hidden" wire:target="search, roleFilter, statusFilter, trashedFilter, previousPage, nextPage, gotoPage" class="hidden">
                            @for($i = 0; $i < 5; $i++)
                                <tr class="opacity-{{ 100 - ($i * 15) }}">
                                    <td class="actions pl-6 pr-2 text-left">
                                        <x-skeleton class="w-4 h-4 rounded-sm" />
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
                                    <td class="actions pr-6 py-3">
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
                    <div wire:loading.class="hidden" wire:target="search, roleFilter, statusFilter, trashedFilter, previousPage, nextPage, gotoPage" class="flex flex-col gap-4">
                        @if($users->isNotEmpty())
                            @foreach($users as $user)
                                <x-card class="p-0 flex flex-col relative transition-colors overflow-hidden {{ $user->trashed() ? 'opacity-75 bg-danger-50/10' : '' }}"
                                     x-bind:class="selectedRows.includes('{{ $user->id }}') ? 'bg-primary-50/50 border-primary-300 ring-1 ring-primary-300' : ''"
                                     wire:key="user-mobile-card-{{ $user->id }}">
                                     
                                    {{-- Cabecera de la Fila --}}
                                    <div class="flex items-center justify-between gap-2 p-4 pb-3 border-b border-border/40 bg-surface-card">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <x-table-checkbox x-model="selectedRows" value="{{ $user->id }}" />
                                            @if($user->avatar_url)
                                                <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="w-8 h-8 rounded-full object-cover shadow-sm shrink-0">
                                            @else
                                                <div class="w-8 h-8 rounded-full bg-primary-600 dark:bg-primary-500 text-white flex items-center justify-center shrink-0 select-none shadow-sm">
                                                    <span class="text-xs-fluid font-bold leading-none inline-flex items-center justify-center">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                                                </div>
                                            @endif
                                            <span class="font-bold text-text-primary text-h3 truncate">{{ $user->name }}</span>
                                            @if($user->trashed())
                                                <x-badge variant="danger" size="sm">Eliminado</x-badge>
                                            @endif
                                        </div>
                                        <div class="flex items-center gap-2 shrink-0">
                                            <x-status-badge :status="$user->active ? 'activo' : 'inactivo'" :map="['activo' => 'success', 'inactivo' => 'danger']" />
                                            
                                            <x-dropdown align="right" width="48">
                                                <x-slot name="trigger">
                                                    <x-button variant="icon" icon="more-vertical" aria-label="Opciones" title="Opciones" />
                                                </x-slot>
                                                <x-slot name="content">
                                                    @if($user->trashed())
                                                        @if(auth()->user()->hasPermission('usuarios.editar'))
                                                            <x-dropdown-link as="button" wire:click="restore({{ $user->id }})" icon="rotate-ccw">Restaurar</x-dropdown-link>
                                                        @endif
                                                        @if(auth()->user()->hasPermission('usuarios.eliminar') && auth()->id() !== $user->id)
                                                            <x-dropdown-link as="button" type="button" @click="$dispatch('confirm-action', { title: 'Eliminar Definitivamente', description: '¿Eliminar permanentemente este usuario? Esta acción destruirá el registro.', confirmLabel: 'Eliminar Definitivamente', variant: 'danger', action: 'forceDelete', params: [{{ $user->id }}] })" danger="true" icon="trash-2">Eliminar Definitivamente</x-dropdown-link>
                                                        @endif
                                                    @else
                                                        @if(auth()->user()->hasPermission('usuarios.editar'))
                                                            <x-dropdown-link as="button" wire:click="openEditModal({{ $user->id }})" icon="pencil">Editar</x-dropdown-link>
                                                        @endif
                                                        @if(auth()->user()->hasPermission('usuarios.editar') && auth()->id() !== $user->id)
                                                            <x-dropdown-link as="button" wire:click="toggleActive({{ $user->id }})" icon="power">{{ $user->active ? 'Desactivar' : 'Activar' }}</x-dropdown-link>
                                                        @endif
                                                        @if(auth()->user()->hasPermission('usuarios.eliminar') && auth()->id() !== $user->id)
                                                            <x-dropdown-link as="button" type="button" @click="$dispatch('confirm-action', { title: 'Confirmar Acción', description: '¿Eliminar este usuario? Esta acción no puede deshacerse.', confirmLabel: 'Eliminar', variant: 'danger', action: 'deleteUser', params: [{{ $user->id }}] })" danger="true" icon="trash-2">Eliminar</x-dropdown-link>
                                                        @endif
                                                    @endif
                                                </x-slot>
                                            </x-dropdown>
                                        </div>
                                    </div>

                                    {{-- Contenido Indentado --}}
                                    <div class="p-4 flex flex-col gap-3">
                                        {{-- Subtítulo --}}
                                        <div class="text-small text-text-muted flex flex-wrap items-center gap-x-4 gap-y-2">
                                            <span class="flex items-center gap-1.5 truncate">
                                                <x-lucide-mail class="w-3.5 h-3.5 shrink-0 opacity-70" />
                                                <span class="truncate">{{ $user->email }}</span>
                                            </span>
                                            <span class="flex items-center gap-1.5 font-medium">
                                                <x-lucide-calendar class="w-3.5 h-3.5 shrink-0 opacity-70" />
                                                <span>{{ $user->created_at->format('d/m/Y') }}</span>
                                            </span>
                                        </div>

                                        {{-- Datos Financieros / Detalles --}}
                                        <div class="grid grid-cols-2 gap-x-4 gap-y-3 pt-3 border-t border-border/40">
                                            <div class="col-span-2">
                                                <p class="text-xs-fluid text-text-muted uppercase font-semibold tracking-wider mb-1">Rol</p>
                                                @if($user->role)
                                                    <x-dynamic-badge :value="$user->role->name" />
                                                @else
                                                    <span class="text-body font-medium text-text-muted">—</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </x-card>
                            @endforeach
                        @elseif($hasActiveFilters)
                            <x-card class="p-8 sm:p-12 text-center">
                                <x-empty-state icon="search" title="No se encontraron usuarios" message="Intenta ajustar tus filtros de búsqueda." />
                            </x-card>
                        @else
                            <x-card class="p-8 sm:p-12 text-center">
                                <x-empty-state icon="users" title="No se encontraron usuarios" message="No hay registros que coincidan con tu búsqueda." />
                            </x-card>
                        @endif
                    </div>

                    {{-- Skeletons Móviles --}}
                    <div wire:loading.class.remove="hidden" wire:target="search, roleFilter, statusFilter, trashedFilter, previousPage, nextPage, gotoPage" class="hidden flex flex-col gap-4">
                        @for($i = 0; $i < 4; $i++)
                            <x-card class="p-4 flex flex-col gap-3 relative transition-colors shadow-sm opacity-{{ 100 - ($i * 15) }}">
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
                            </x-card>
                        @endfor
                    </div>
                </div>
            </div>
        </div>

        {{-- Bulk Actions Bar --}}
        @if(auth()->user()->hasPermission('usuarios.eliminar') || auth()->user()->hasPermission('*'))
            <x-bulk-actions-bar>
                @if($trashedFilter === 'trashed')
                    <x-button
                        @click="$dispatch('confirm-action', {
                            title: 'Eliminar Definitivamente',
                            description: 'Se eliminarán permanentemente los usuarios seleccionados de la base de datos (excepto el tuyo propio).',
                            confirmLabel: 'Destruir Registros',
                            variant: 'danger',
                            action: 'bulkDelete',
                            params: []
                        })"
                        variant="danger"
                        icon="trash-2">
                        Eliminar Definitivamente
                    </x-button>
                @else
                    <x-button
                        @click="$dispatch('confirm-action', {
                            title: 'Eliminar Usuarios',
                            description: 'Se eliminarán los usuarios seleccionados (excepto el tuyo propio).',
                            confirmLabel: 'Eliminar',
                            variant: 'danger',
                            action: 'bulkDelete',
                            params: []
                        })"
                        variant="danger"
                        icon="trash-2">
                        Eliminar
                    </x-button>
                @endif
            </x-bulk-actions-bar>
        @endif
        {{-- Pagination Footer --}}
        @if($users->total() > 0)
            <x-card.footer>
                {{ $users->links(data: ['scrollTo' => false]) }}
            </x-card.footer>
        @endif
    </div>

    {{-- Delete / Action Modals --}}
{{-- Modal Unificado Crear/Editar Usuario --}}
    @if($showModal)
        <x-modal show="showModal" :title="$editingId ? 'Editar usuario' : 'Nuevo usuario'">
            <form wire:submit="saveUser" class="p-5 space-y-4" autocomplete="off">
                {{-- Fotografía / Avatar del Usuario (Componente Atómico con previsualización RAM Zero-Flicker y sin bordes) --}}
                <x-avatar-upload wire:model="photo" :current-url="$currentAvatarUrl" :name="$name" />

                <x-form-field label="Nombre completo" required error="{{ $errors->first('name') }}">
                    <input wire:model="name" type="text" class="input" placeholder="Ej. Juan Pérez" autocomplete="off">
                </x-form-field>

                <x-form-field label="Correo electrónico" required error="{{ $errors->first('email') }}">
                    <input wire:model="email" type="email" class="input" placeholder="ejemplo@empresa.com" autocomplete="off">
                </x-form-field>

                <div class="grid grid-cols-2 gap-4">
                    <x-form-field :label="$editingId ? 'Nueva contraseña' : 'Contraseña'" :required="!$editingId"
                        error="{{ $errors->first('password') }}">
                        <x-password-input wire:model="password"
                            placeholder="{{ $editingId ? 'Dejar en blanco para mantener actual' : 'Mínimo 6 caracteres' }}"
                            autocomplete="new-password" />
                    </x-form-field>
                    <x-form-field label="Rol asignado" required error="{{ $errors->first('role_id') }}">
                        <x-custom-select wire:model="role_id" :options="$roles->pluck('name', 'id')->toArray()"
                            placeholder="Selecciona un rol..." />
                    </x-form-field>
                </div>

                <div class="pt-2">
                    <x-toggle wire:model="active" label="Usuario activo" description="{{ auth()->id() === $editingId ? 'No puedes desactivar tu propia cuenta.' : 'Los usuarios inactivos no tienen acceso al sistema.' }}" :disabled="auth()->id() === $editingId" />
                </div>

                <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3 pt-4 border-t border-border">
                    <x-button wire:click="$set('showModal', false)" variant="soft">Cancelar</x-button>
                    <x-button type="submit" variant="primary" target="saveUser">{{ $editingId ? 'Guardar cambios' : 'Crear usuario' }}</x-button>
                </div>
            </form>
        </x-modal>
    @endif
    <x-confirm-modal />
</div>