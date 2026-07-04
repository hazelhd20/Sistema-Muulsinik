<div x-data="clientIndex(@entangle('selectedRows'))" x-init="totalOnPageStatic = {{ $clients->count() }}; init()" data-total-on-page="{{ $clients->count() }}">
    {{-- Header --}}
    <x-page-header subtitle="Catálogos" title="Clientes">
        <x-slot:actions>
            @if(auth()->user()->hasPermission('catalogos.editar') || auth()->user()->hasPermission('*'))
                <x-button wire:click="openCreateModal" variant="primary" icon="plus">
                    Nuevo Cliente
                </x-button>
            @endif
        </x-slot:actions>
    </x-page-header>

    {{-- Unified Datagrid Card Container --}}
    <div class="mt-0 flex flex-col bg-transparent md:bg-surface-card md:border md:border-border md:rounded-xl">
        @php
            $activeCount = ($activeFilter !== '' ? 1 : 0);
            $hasActiveFilters = !empty($search) || $activeCount > 0;
        @endphp

        @if($clients->isNotEmpty() || $hasActiveFilters)
            {{-- Header Group --}}
            <div class="bg-transparent border-0 shadow-none md:card md:rounded-t-xl md:bg-surface-card md:border-0 md:shadow-none mb-4 md:mb-0">
                <div class="flex flex-row gap-2.5 items-center justify-between w-full py-1 md:px-6 md:py-4">
                    <div class="flex-1 min-w-0">
                        <x-search-input wire:model.live.debounce.300ms="search" placeholder="Buscar por nombre, RFC o email..." />
                    </div>

                    {{-- Filters Popover --}}
                    <x-filters-popover :activeCount="$activeCount" :columns="1" @filters-opened="initFilters()">
                        <x-form-field label="Estado">
                            <x-custom-select x-model="filterActive" :options="['1' => 'Activos', '0' => 'Inactivos']"
                                placeholder="Todos" />
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
                <div class="flex flex-wrap items-center gap-2 pb-3 md:px-6 md:pb-4 pt-1">
                    @if($activeFilter !== '')
                        <x-filter-chip label="Estado" :value="$activeFilter === '1' ? 'Activos' : 'Inactivos'" wire:click="$set('activeFilter', '')" />
                    @endif
                </div>
                @endif
            </div>
        @endif

        <div class="relative">
            <div class="w-full">
                <x-card.table class="hidden md:block w-full">
                @if($clients->isEmpty() && !$hasActiveFilters)
                    <div wire:loading.class="hidden" wire:target="search, activeFilter, previousPage, nextPage, gotoPage" class="p-8">
                        <x-empty-state icon="users" title="No hay clientes" message="Aún no has registrado ningún cliente en el catálogo." />
                    </div>
                @endif
                <table class="w-full table-fixed min-w-[1000px] {{ $clients->isEmpty() && !$hasActiveFilters ? 'hidden' : '' }}"
                    @if($clients->isEmpty()) wire:loading.class.remove="hidden" wire:target="search, activeFilter, previousPage, nextPage, gotoPage" @endif>
                    <colgroup>
                        <col class="w-14">
                        <col class="w-[30%]">
                        <col class="w-[20%]">
                        <col class="w-[20%]">
                        <col class="w-[15%]">
                        <col class="w-28">
                    </colgroup>
                    <thead class="bg-surface-th border-b border-border/40">
                        <tr>
                            <th class="actions pl-6 pr-2 text-left">
                                <x-table-checkbox x-bind:checked="allSelected"
                                    @change="toggleAll({{ json_encode($clients->pluck('id')->toArray()) }})" />
                            </th>
                            <x-sortable-header field="name" label="Nombre Comercial" :sortField="$sortField" :sortDirection="$sortDirection" />
                            <x-sortable-header field="rfc" label="RFC" :sortField="$sortField" :sortDirection="$sortDirection" />
                            <th class="text-xs-fluid font-semibold uppercase tracking-wider text-text-muted">Contacto</th>
                            <th class="text-xs-fluid font-semibold uppercase tracking-wider text-text-muted">Estado</th>
                            <th class="actions pr-6 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody wire:loading.class="hidden" wire:target="search, activeFilter, previousPage, nextPage, gotoPage">
                        @if($clients->isEmpty() && $hasActiveFilters)
                            <tr>
                                <td colspan="6" class="p-8">
                                    <x-empty-state icon="search" title="No se encontraron clientes" message="Intenta con otro término de búsqueda." />
                                </td>
                            </tr>
                        @else
                            @foreach($clients as $client)
                                <tr wire:key="client-row-{{ $client->id }}" class="group hover:bg-surface-hover transition-colors duration-150" :class="selectedRows.includes('{{ $client->id }}') ? 'bg-primary-50/50' : ''">
                                    <td class="actions pl-6 pr-2 text-left" @click.stop="$event.stopPropagation()">
                                        <x-table-checkbox x-model="selectedRows" value="{{ $client->id }}" />
                                    </td>
                                    <td class="max-w-0">
                                        <p class="text-body font-bold text-text-primary truncate" title="{{ $client->name }}">{{ $client->name }}</p>
                                        @if($client->legal_name)
                                            <p class="text-xs-fluid text-text-muted truncate" title="{{ $client->legal_name }}">{{ $client->legal_name }}</p>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="text-body text-text-secondary font-mono {{ !$client->rfc ? 'text-text-muted italic !font-sans' : '' }}">
                                            {{ $client->rfc ?: 'Sin RFC' }}
                                        </span>
                                    </td>
                                    <td class="max-w-0">
                                        @if($client->email)
                                            <p class="text-small text-text-secondary truncate"><x-lucide-mail class="w-3 h-3 inline mr-1 text-text-muted"/>{{ $client->email }}</p>
                                        @endif
                                        @if($client->phone)
                                            <p class="text-small text-text-secondary truncate"><x-lucide-phone class="w-3 h-3 inline mr-1 text-text-muted"/>{{ $client->phone }}</p>
                                        @endif
                                        @if(!$client->email && !$client->phone)
                                            <span class="text-text-muted italic text-small">Sin contacto</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($client->active)
                                            <x-badge variant="success">Activo</x-badge>
                                        @else
                                            <x-badge variant="danger">Inactivo</x-badge>
                                        @endif
                                    </td>
                                    <td class="actions pr-6 py-3" @click.stop="$event.stopPropagation()">
                                        <div class="flex items-center justify-end">
                                            <x-dropdown align="right" width="48">
                                                <x-slot name="trigger">
                                                    <x-button variant="icon" icon="more-vertical" aria-label="Opciones" title="Opciones" />
                                                </x-slot>
                                                <x-slot name="content">
                                                    @if(auth()->user()->hasPermission('catalogos.editar') || auth()->user()->hasPermission('*'))
                                                        <x-dropdown-link as="button" wire:click="openEditModal({{ $client->id }})" icon="pencil">
                                                            Editar
                                                        </x-dropdown-link>
                                                        <x-dropdown-link as="button" wire:click="toggleActive({{ $client->id }})" icon="power">
                                                            {{ $client->active ? 'Desactivar' : 'Activar' }}
                                                        </x-dropdown-link>
                                                    @endif
                                                    @if(auth()->user()->hasPermission('catalogos.eliminar') || auth()->user()->hasPermission('*'))
                                                        <x-dropdown-link as="button" type="button" @click="$dispatch('confirm-action', { title: 'Confirmar Acción', description: '¿Eliminar este cliente? Esta acción no puede deshacerse.', confirmLabel: 'Eliminar', variant: 'danger', action: 'deleteClient', params: [{{ $client->id }}] })" danger="true" icon="trash-2">
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
                    <tbody wire:loading.class.remove="hidden" wire:target="search, activeFilter, previousPage, nextPage, gotoPage" class="hidden">
                        @for($i = 0; $i < 5; $i++)
                            <tr class="opacity-{{ 100 - ($i * 15) }}">
                                <td class="actions pl-6 pr-2 text-left">
                                    <x-skeleton class="w-4 h-4 rounded-sm" />
                                </td>
                                <td>
                                    <x-skeleton class="h-4 rounded w-48 mb-1.5" />
                                    <x-skeleton class="h-3 rounded w-32" />
                                </td>
                                <td>
                                    <x-skeleton class="h-4 rounded w-24" />
                                </td>
                                <td>
                                    <x-skeleton class="h-4 rounded w-32 mb-1.5" />
                                    <x-skeleton class="h-4 rounded w-24" />
                                </td>
                                <td>
                                    <x-skeleton class="h-5 rounded w-16 rounded-full" />
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

                {{-- Mobile view --}}
                <div class="md:hidden flex flex-col gap-4 mt-2">
                    <div wire:loading.class="hidden" wire:target="search, activeFilter, previousPage, nextPage, gotoPage" class="flex flex-col gap-4">
                        @if($clients->isNotEmpty())
                            @foreach($clients as $client)
                                <x-card class="p-0 flex flex-col relative transition-colors overflow-hidden"
                                     x-bind:class="selectedRows.includes('{{ $client->id }}') ? 'bg-primary-50/50 border-primary-300 ring-1 ring-primary-300' : ''"
                                     wire:key="client-mobile-card-{{ $client->id }}">
                                    <div class="flex items-center justify-between gap-2 p-4 pb-3 border-b border-border/40 bg-surface-card">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <x-table-checkbox x-model="selectedRows" value="{{ $client->id }}" />
                                            <span class="font-bold text-text-primary text-h3 truncate">{{ $client->name }}</span>
                                        </div>
                                        <div class="flex items-center gap-2 shrink-0">
                                            @if($client->active)
                                                <x-badge variant="success">Activo</x-badge>
                                            @else
                                                <x-badge variant="danger">Inactivo</x-badge>
                                            @endif
                                            <x-dropdown align="right" width="48">
                                                <x-slot name="trigger">
                                                    <x-button variant="icon" icon="more-vertical" aria-label="Opciones" title="Opciones" />
                                                </x-slot>
                                                <x-slot name="content">
                                                    @if(auth()->user()->hasPermission('catalogos.editar') || auth()->user()->hasPermission('*'))
                                                        <x-dropdown-link as="button" wire:click="openEditModal({{ $client->id }})" icon="pencil">Editar</x-dropdown-link>
                                                        <x-dropdown-link as="button" wire:click="toggleActive({{ $client->id }})" icon="power">{{ $client->active ? 'Desactivar' : 'Activar' }}</x-dropdown-link>
                                                    @endif
                                                    @if(auth()->user()->hasPermission('catalogos.eliminar') || auth()->user()->hasPermission('*'))
                                                        <x-dropdown-link as="button" type="button" @click="$dispatch('confirm-action', { title: 'Confirmar Acción', description: '¿Eliminar este cliente?', confirmLabel: 'Eliminar', variant: 'danger', action: 'deleteClient', params: [{{ $client->id }}] })" danger="true" icon="trash-2">Eliminar</x-dropdown-link>
                                                    @endif
                                                </x-slot>
                                            </x-dropdown>
                                        </div>
                                    </div>
                                    <div class="p-4 flex flex-col gap-4">
                                        @if($client->legal_name || $client->rfc)
                                            <div class="text-small text-text-muted flex flex-wrap items-center gap-x-4 gap-y-2">
                                                @if($client->legal_name)
                                                    <span class="flex items-center gap-1.5 truncate font-medium">
                                                        <x-lucide-building-2 class="w-3.5 h-3.5 shrink-0 opacity-70" />
                                                        <span class="truncate">{{ $client->legal_name }}</span>
                                                    </span>
                                                @endif
                                                @if($client->rfc)
                                                    <span class="flex items-center gap-1.5 font-mono uppercase">
                                                        <x-lucide-file-text class="w-3.5 h-3.5 shrink-0 opacity-70" />
                                                        <span>{{ $client->rfc }}</span>
                                                    </span>
                                                @endif
                                            </div>
                                        @endif

                                        <div class="grid grid-cols-2 gap-x-4 gap-y-3 pt-3 border-t border-border/40">
                                            <div>
                                                <p class="text-xs-fluid text-text-muted uppercase font-semibold tracking-wider mb-1">Email</p>
                                                <p class="text-body text-text-secondary truncate">{{ $client->email ?: '—' }}</p>
                                            </div>
                                            <div>
                                                <p class="text-xs-fluid text-text-muted uppercase font-semibold tracking-wider mb-1">Teléfono</p>
                                                <p class="text-body text-text-secondary">{{ $client->phone ?: '—' }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </x-card>
                            @endforeach
                        @elseif($hasActiveFilters)
                            <x-card class="p-8 sm:p-12 text-center">
                                <x-empty-state icon="search" title="No se encontraron clientes" message="Intenta ajustar tus filtros." />
                            </x-card>
                        @else
                            <x-card class="p-8 sm:p-12 text-center">
                                <x-empty-state icon="users" title="No hay clientes" message="Aún no has registrado ningún cliente en el catálogo." />
                            </x-card>
                        @endif
                    </div>

                    {{-- Mobile Skeletons --}}
                    <div wire:loading.class.remove="hidden" wire:target="search, activeFilter, previousPage, nextPage, gotoPage" class="hidden flex flex-col gap-4 mt-2">
                        @for($i = 0; $i < 4; $i++)
                            <x-card class="p-4 flex flex-col gap-3 relative transition-colors shadow-sm opacity-{{ 100 - ($i * 15) }}">
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <x-skeleton class="w-4 h-4 rounded-sm shrink-0" />
                                        <x-skeleton class="h-5 w-48 rounded" />
                                    </div>
                                    <x-skeleton class="w-7 h-7 rounded-md" />
                                </div>
                                <div class="pl-8 flex flex-col gap-3">
                                    <div class="grid grid-cols-2 gap-x-4 gap-y-3">
                                        <div>
                                            <x-skeleton class="h-2 w-12 mb-1.5 rounded" />
                                            <x-skeleton class="h-4 w-24 rounded" />
                                        </div>
                                        <div>
                                            <x-skeleton class="h-2 w-12 mb-1.5 rounded" />
                                            <x-skeleton class="h-5 w-16 rounded-full" />
                                        </div>
                                    </div>
                                </div>
                            </x-card>
                        @endfor
                    </div>
                </div>
            </div>
        </div>

        @if(auth()->user()->hasPermission('catalogos.eliminar') || auth()->user()->hasPermission('*'))
        <x-bulk-actions-bar>
            <x-button @click="$dispatch('confirm-action', { title: 'Eliminar Clientes', description: 'Se eliminarán los clientes seleccionados.', confirmLabel: 'Eliminar', variant: 'danger', action: 'bulkDelete', params: [] })" variant="danger" icon="trash-2">
                Eliminar
            </x-button>
        </x-bulk-actions-bar>
        @endif

        @if($clients->total() > 0)
            <x-card.footer>
                {{ $clients->links(data: ['scrollTo' => false]) }}
            </x-card.footer>
        @endif
    </div>

    @if($showCreateModal)
        <x-modal show="showCreateModal" :title="$editingId ? 'Editar Cliente' : 'Nuevo Cliente'" maxWidth="md">
            <form wire:submit="saveClient" class="p-5 space-y-4">
                <x-form-field label="Nombre Comercial / Razón Social" required error="{{ $errors->first('name') }}">
                    <input wire:model="name" type="text" class="input" placeholder="Ej. Constructora del Sur S.A.">
                </x-form-field>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-form-field label="Nombre Legal (Opcional)" error="{{ $errors->first('legal_name') }}">
                        <input wire:model="legal_name" type="text" class="input" placeholder="Razón social fiscal">
                    </x-form-field>
                    <x-form-field label="RFC (Opcional)" error="{{ $errors->first('rfc') }}">
                        <input wire:model="rfc" type="text" class="input uppercase" placeholder="XAXX010101000">
                    </x-form-field>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-form-field label="Correo Electrónico" error="{{ $errors->first('email') }}">
                        <input wire:model="email" type="email" class="input" placeholder="contacto@empresa.com">
                    </x-form-field>
                    <x-form-field label="Teléfono" error="{{ $errors->first('phone') }}">
                        <input wire:model="phone" type="text" class="input" placeholder="55 1234 5678">
                    </x-form-field>
                </div>
                <div class="pt-2">
                    <x-toggle wire:model="active" label="Cliente Activo" description="Los clientes inactivos no aparecerán en el cotizador." />
                </div>
                <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3 pt-4 border-t border-border">
                    <x-button wire:click="$set('showCreateModal', false)" variant="soft">Cancelar</x-button>
                    <x-button type="submit" variant="primary" target="saveClient">
                        {{ $editingId ? 'Guardar Cambios' : 'Crear Cliente' }}
                    </x-button>
                </div>
            </form>
        </x-modal>
    @endif

    <x-confirm-modal />
</div>
