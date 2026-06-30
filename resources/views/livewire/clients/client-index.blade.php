<div x-data="clientIndex(@entangle('selectedRows'))" x-init="totalOnPageStatic = {{ $clients->count() }}; init()" data-total-on-page="{{ $clients->count() }}">
    {{-- Header --}}
    <x-page-header subtitle="Catálogos" title="Clientes">
        <x-slot:actions>
            <x-button wire:click="openCreateModal" variant="primary" icon="plus">
                Nuevo Cliente
            </x-button>
        </x-slot:actions>
    </x-page-header>

    {{-- Unified Datagrid Card Container --}}
    <div class="mt-4 mb-6 flex flex-col bg-transparent md:bg-surface-card md:border md:border-border md:rounded-lg">
        @php
            $activeCount = ($activeFilter !== '' ? 1 : 0);
            $hasActiveFilters = !empty($search) || $activeCount > 0;
        @endphp

        @if($clients->isNotEmpty() || $hasActiveFilters)
            {{-- Header Group --}}
            <div class="card md:rounded-t-lg md:bg-surface-card md:border-0 md:shadow-none mb-4 md:mb-0">
                <div class="flex flex-row gap-3 items-center justify-between w-full p-4 md:px-6 md:py-4">
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
                <div class="flex flex-wrap items-center gap-2 px-4 pb-4 md:px-6 md:pb-4 pt-0">
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
                            <th class="actions text-center pl-4 pr-2">
                                <input type="checkbox"
                                    class="w-4 h-4 rounded-sm text-primary-600 focus:ring-primary-500 border-border bg-surface-card cursor-pointer"
                                    x-bind:checked="allSelected"
                                    x-on:change="toggleAll({{ json_encode($clients->pluck('id')->toArray()) }})" />
                            </th>
                            <x-sortable-header field="name" label="Nombre Comercial" :sortField="$sortField" :sortDirection="$sortDirection" />
                            <x-sortable-header field="rfc" label="RFC" :sortField="$sortField" :sortDirection="$sortDirection" />
                            <th>Contacto</th>
                            <th>Estado</th>
                            <th class="actions text-right pr-4">Acciones</th>
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
                                    <td class="actions pl-4 pr-2 text-center" @click.stop>
                                        <x-table-checkbox x-model="selectedRows" value="{{ $client->id }}" />
                                    </td>
                                    <td class="max-w-0">
                                        <p class="font-semibold text-text-primary truncate" title="{{ $client->name }}">{{ $client->name }}</p>
                                        @if($client->legal_name)
                                            <p class="text-xs text-text-muted truncate" title="{{ $client->legal_name }}">{{ $client->legal_name }}</p>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="text-text-secondary {{ !$client->rfc ? 'text-text-muted italic' : '' }}">
                                            {{ $client->rfc ?: 'Sin RFC' }}
                                        </span>
                                    </td>
                                    <td class="max-w-0">
                                        @if($client->email)
                                            <p class="text-sm text-text-secondary truncate"><x-lucide-mail class="w-3 h-3 inline mr-1 text-text-muted"/>{{ $client->email }}</p>
                                        @endif
                                        @if($client->phone)
                                            <p class="text-sm text-text-secondary truncate"><x-lucide-phone class="w-3 h-3 inline mr-1 text-text-muted"/>{{ $client->phone }}</p>
                                        @endif
                                        @if(!$client->email && !$client->phone)
                                            <span class="text-text-muted italic text-sm">Sin contacto</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($client->active)
                                            <x-badge variant="success">Activo</x-badge>
                                        @else
                                            <x-badge variant="danger">Inactivo</x-badge>
                                        @endif
                                    </td>
                                    <td class="actions pr-4 py-3" @click.stop>
                                        <div class="flex items-center justify-end">
                                            <x-dropdown align="right" width="48">
                                                <x-slot name="trigger">
                                                    <x-button variant="icon" icon="more-vertical" aria-label="Opciones" title="Opciones" />
                                                </x-slot>
                                                <x-slot name="content">
                                                    <x-dropdown-link as="button" wire:click="openEditModal({{ $client->id }})" icon="pencil">
                                                        Editar
                                                    </x-dropdown-link>
                                                    <x-dropdown-link as="button" wire:click="toggleActive({{ $client->id }})" icon="power">
                                                        {{ $client->active ? 'Desactivar' : 'Activar' }}
                                                    </x-dropdown-link>
                                                    <x-dropdown-link as="button" type="button" @click="$dispatch('confirm-action', { title: 'Confirmar Acción', description: '¿Eliminar este cliente? Esta acción no puede deshacerse.', confirmLabel: 'Eliminar', variant: 'danger', action: 'deleteClient', params: [{{ $client->id }}] })" danger="true" icon="trash-2">
                                                        Eliminar
                                                    </x-dropdown-link>
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
                                <td class="actions pl-4 pr-2 text-center">
                                    <x-skeleton class="w-4 h-4 rounded-sm mx-auto" />
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
                                <td class="actions pr-4 py-3">
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
                                <div class="card p-4 flex flex-col gap-3 relative transition-colors shadow-sm"
                                     :class="selectedRows.includes('{{ $client->id }}') ? 'bg-primary-50/50 border-primary-300' : ''">
                                    <div class="flex items-center justify-between gap-2">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <x-table-checkbox x-model="selectedRows" value="{{ $client->id }}" />
                                            <span class="font-bold text-text-primary text-base truncate">{{ $client->name }}</span>
                                        </div>
                                        <div class="flex items-center gap-2 shrink-0">
                                            <x-dropdown align="right" width="48">
                                                <x-slot name="trigger">
                                                    <x-button variant="icon" icon="more-vertical" aria-label="Opciones" title="Opciones" />
                                                </x-slot>
                                                <x-slot name="content">
                                                    <x-dropdown-link as="button" wire:click="openEditModal({{ $client->id }})" icon="pencil">Editar</x-dropdown-link>
                                                    <x-dropdown-link as="button" wire:click="toggleActive({{ $client->id }})" icon="power">{{ $client->active ? 'Desactivar' : 'Activar' }}</x-dropdown-link>
                                                    <x-dropdown-link as="button" type="button" @click="$dispatch('confirm-action', { title: 'Confirmar Acción', description: '¿Eliminar este cliente?', confirmLabel: 'Eliminar', variant: 'danger', action: 'deleteClient', params: [{{ $client->id }}] })" danger="true" icon="trash-2">Eliminar</x-dropdown-link>
                                                </x-slot>
                                            </x-dropdown>
                                        </div>
                                    </div>
                                    <div class="pl-8 flex flex-col gap-3">
                                        <div class="grid grid-cols-2 gap-x-4 gap-y-3">
                                            <div>
                                                <p class="text-2xs text-text-muted uppercase font-semibold mb-0.5">RFC</p>
                                                <p class="text-xs text-text-secondary">{{ $client->rfc ?: '—' }}</p>
                                            </div>
                                            <div>
                                                <p class="text-2xs text-text-muted uppercase font-semibold mb-0.5">Estado</p>
                                                @if($client->active)
                                                    <x-badge variant="success">Activo</x-badge>
                                                @else
                                                    <x-badge variant="danger">Inactivo</x-badge>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @elseif($hasActiveFilters)
                            <div class="p-12">
                                <x-empty-state icon="search" title="No se encontraron clientes" message="Intenta ajustar tus filtros." />
                            </div>
                        @endif
                    </div>

                    {{-- Mobile Skeletons --}}
                    <div wire:loading.class.remove="hidden" wire:target="search, activeFilter, previousPage, nextPage, gotoPage" class="hidden flex flex-col gap-4 mt-2">
                        @for($i = 0; $i < 4; $i++)
                            <div class="card p-4 flex flex-col gap-3 relative transition-colors shadow-sm opacity-{{ 100 - ($i * 15) }}">
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
                            </div>
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
                <div class="flex justify-end gap-3 pt-4 border-t border-border">
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
