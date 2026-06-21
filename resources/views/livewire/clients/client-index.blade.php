<div x-data="productIndex(@entangle('selectedRows'))" x-init="totalOnPage = {{ $clients->count() }}; init()">
    {{-- Header --}}
    <x-page-header subtitle="Catálogos" title="Clientes">
        <x-slot:actions>
            <x-button wire:click="openCreateModal" variant="primary" icon="plus">
                Nuevo Cliente
            </x-button>
        </x-slot:actions>
    </x-page-header>

    {{-- Unified Datagrid Card Container --}}
    <div class="mt-4 mb-6 flex flex-col bg-transparent md:bg-surface-card md:border md:border-border md:rounded-[10px] md:shadow-sm">
        @if($clients->isNotEmpty() || !empty($search))
            {{-- Header Group --}}
            <div class="card md:rounded-t-[10px] md:bg-surface-card md:border-0 md:shadow-none mb-4 md:mb-0">
                <div class="flex flex-row gap-3 items-center justify-between w-full p-4 md:px-6 md:py-4">
                    <div class="flex-1 min-w-0">
                        <x-search-input wire:model.live.debounce.300ms="search" placeholder="Buscar por nombre, RFC o email..." />
                    </div>
                </div>
            </div>
        @endif

        <div class="relative">
            <div class="w-full">
                <x-card.table class="hidden md:block w-full">
                @if($clients->isEmpty() && empty($search))
                    <div class="p-8">
                        <x-empty-state icon="users" title="No hay clientes" message="Aún no has registrado ningún cliente en el catálogo." />
                    </div>
                @endif
                <table class="w-full table-fixed min-w-[1000px] {{ $clients->isEmpty() && empty($search) ? 'hidden' : '' }}"
                    @if($clients->isEmpty()) wire:loading.class.remove="hidden" wire:target="search, previousPage, nextPage, gotoPage" @endif>
                    <colgroup>
                        <col class="w-14">
                        <col class="w-[30%]">
                        <col class="w-[20%]">
                        <col class="w-[20%]">
                        <col class="w-[15%]">
                        <col class="w-28">
                    </colgroup>
                    <thead class="bg-surface-main/50 border-b border-border">
                        <tr>
                            <th class="actions text-center pl-4 pr-2">
                                <input type="checkbox"
                                    class="w-4 h-4 rounded-sm text-primary-600 focus:ring-primary-500 border-border bg-surface-card cursor-pointer"
                                    x-bind:checked="allSelected"
                                    x-on:change="toggleAll([{{ $clients->pluck('id')->join(',') }}])" />
                            </th>
                            <x-sortable-header field="name" label="Nombre Comercial" :sortField="$sortField" :sortDirection="$sortDirection" />
                            <x-sortable-header field="rfc" label="RFC" :sortField="$sortField" :sortDirection="$sortDirection" />
                            <th>Contacto</th>
                            <th>Estado</th>
                            <th class="actions text-right pr-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody wire:loading.class="hidden" wire:target="search, previousPage, nextPage, gotoPage">
                        @if($clients->isEmpty() && !empty($search))
                            <tr>
                                <td colspan="6" class="p-8">
                                    <x-empty-state icon="search" title="No se encontraron clientes" message="Intenta con otro término de búsqueda." />
                                </td>
                            </tr>
                        @else
                            @foreach($clients as $client)
                                <tr wire:key="client-row-{{ $client->id }}" class="group hover:bg-surface-hover/80 transition-colors duration-150" :class="selectedRows.includes('{{ $client->id }}') ? 'bg-primary-50/50' : ''">
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
                                                    <x-button variant="icon" icon="more-vertical" class="text-text-muted hover:text-text-primary" />
                                                </x-slot>
                                                <x-slot name="content">
                                                    <x-dropdown-link as="button" wire:click="openEditModal({{ $client->id }})" icon="pencil">
                                                        Editar
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
                </table>
                </x-card.table>

                {{-- Mobile view (omitted for brevity but maintaining structure) --}}
                <div class="md:hidden flex flex-col gap-4 mt-2">
                    <div wire:loading.class="hidden" wire:target="search, previousPage, nextPage, gotoPage" class="flex flex-col gap-4">
                        @if($clients->isNotEmpty())
                            @foreach($clients as $client)
                                <div class="card p-4 flex flex-col gap-3 relative transition-colors shadow-sm">
                                    <div class="flex items-center justify-between gap-2">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <x-table-checkbox x-model="selectedRows" value="{{ $client->id }}" />
                                            <span class="font-bold text-text-primary text-base truncate">{{ $client->name }}</span>
                                        </div>
                                        <x-button variant="icon" icon="pencil" wire:click="openEditModal({{ $client->id }})" />
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <x-bulk-actions-bar>
            <x-button @click="$dispatch('confirm-action', { title: 'Eliminar Clientes', description: 'Se eliminarán los clientes seleccionados.', confirmLabel: 'Eliminar', variant: 'danger', action: 'bulkDelete', params: [] })" variant="danger" icon="trash-2">
                Eliminar
            </x-button>
        </x-bulk-actions-bar>

        @if($clients->hasPages())
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
                    <x-button wire:click="$set('showCreateModal', false)" variant="secondary">Cancelar</x-button>
                    <x-button type="submit" variant="primary" target="saveClient">
                        {{ $editingId ? 'Guardar Cambios' : 'Crear Cliente' }}
                    </x-button>
                </div>
            </form>
        </x-modal>
    @endif

    <x-confirm-modal />
</div>
